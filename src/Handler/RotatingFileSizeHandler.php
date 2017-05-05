<?php declare(strict_types=1);

namespace MonologConfig\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class RotatingFileSizeHandler extends StreamHandler
{
    const FILENAME_TOKEN_FILENAME = '{fileName}';
    const FILENAME_TOKEN_DATE     = '{date}';
    const FILENAME_TOKEN_ROTATION = '{rotation}';

    protected $fileName;
    protected $fileSize;
    protected $rotateFile = false;
    protected $fileNameFormat = self::FILENAME_TOKEN_FILENAME . '-' . self::FILENAME_TOKEN_DATE;
    protected $dateFormat = 'Ymd';
    protected $gzipCompressionLevel;
    protected $gzipCompressionSuffix = 'gz';

    /**
     * RotatingFileSizeHandler constructor.
     *
     * @param string $filename
     * @param float $filesize file size in MB, use 0 for infinite file size
     * @param int $compression gzip compression level [0-9]; use 9 for maximum and 0 for no compression
     * @param bool|int $level
     * @param bool $bubble
     * @param null $filePermission
     * @param bool $useLocking
     */
    public function __construct(
        string $filename,
        float $filesize = 1.0,
        int $compression = 0,
        int $level = Logger::DEBUG,
        bool $bubble = true,
        $filePermission = null,
        bool $useLocking = false
    ) {
        $this->fileName = $filename;
        $this->fileSize = (float)$filesize;
        $this->setGzipCompressionLevel($compression);

        parent::__construct($filename, $level, $bubble, $filePermission, $useLocking);
    }

    private function normalizeCompressionLevel(int $level): int
    {
        $level = (int)$level;

        // normalize level
        if ($level < 0) {
            $level = 0;
        }

        if ($level > 9) {
            $level = 9;
        }

        return $level;
    }

    /**
     * @param int $level
     */
    public function setGzipCompressionLevel(int $level)
    {
        $this->gzipCompressionLevel = $this->normalizeCompressionLevel($level);
    }

    public function getGzipCompressionLevel(): int
    {
        return $this->gzipCompressionLevel;
    }

    protected function mustRotate()
    {
        if (! file_exists($this->url)) {
            return false;
        }

        return $this->fileSizeExceeded();
    }

    protected function fileSizeExceeded()
    {
        if ($this->fileSize > 0) {
            // convert file size from mb to bytes
            $maxFileSize = $this->fileSize * pow(1024, 2);
            return filesize($this->url) > $maxFileSize;
        }
        return false;
    }

    public function close()
    {
        parent::close();

        if ($this->rotateFile === true) {
            $this->rotate();
        }
    }

    protected function write(array $record)
    {
        $this->rotateFile = $this->mustRotate();

        if ($this->rotateFile === true) {
            $this->close();
        }

        parent::write($record);
    }

    private function useGzipCompression()
    {
        return $this->gzipCompressionLevel > 0;
    }

    /**
     * Builds a file name for the rotated log file based
     * on the current file name format.
     *
     * @param int $rotationCount
     * @return string
     */
    protected function getFormattedFileName(int $rotationCount = 0): string
    {
        $fileNamePattern = $this->fileNameFormat;
        $fileInfo = pathinfo($this->fileName);

        // if $rotationCount > 0 and the pattern does no yet include the token, add it
        if ($rotationCount > 0  && false === strrpos($fileNamePattern, self::FILENAME_TOKEN_ROTATION)) {
            $fileNamePattern .= '-' . self::FILENAME_TOKEN_ROTATION;
        }

        $formattedFileName = str_replace(
            [
                self::FILENAME_TOKEN_FILENAME,
                self::FILENAME_TOKEN_DATE,
                self::FILENAME_TOKEN_ROTATION
            ],
            [
                $fileInfo['filename'],
                date($this->dateFormat),
                $rotationCount
            ],
            "{$fileInfo['dirname']}/{$fileNamePattern}"
        );

        if (! empty($fileInfo['extension'])) {
            $formattedFileName .= ".{$fileInfo['extension']}";
        }

        if ($this->useGzipCompression()) {
            $formattedFileName .= ".{$this->gzipCompressionSuffix}";
        }

        return $formattedFileName;
    }

    private function compressFileContent(string $fileName)
    {
        $compressedContent = gzencode(
            file_get_contents($fileName),
            $this->gzipCompressionLevel
        );

        if (is_writable($fileName)) {
            // suppress errors if renaming fails due to concurrent access
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            });
            file_put_contents($fileName, $compressedContent);
            restore_error_handler();
        }
    }

    protected function rotate()
    {
        $rotationFileName = $this->getFormattedFileName();

        // if the filename already exists, increment rotation counter
        $rotationCount = 0;
        while (file_exists($rotationFileName)) {
            $rotationCount++;
            $rotationFileName = $this->getFormattedFileName($rotationCount);
        }

        // rename existing file
        if (is_writable($this->url)) {
            // suppress errors if renaming fails due to concurrent access
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            });
            rename($this->url, $rotationFileName);
            restore_error_handler();
        }

        if ($this->useGzipCompression()) {
            $this->compressFileContent($rotationFileName);
        }

        $this->rotateFile = false;
    }
}

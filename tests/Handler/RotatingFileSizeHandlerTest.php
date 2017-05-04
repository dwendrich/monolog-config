<?php

namespace MonologConfig\Test\Handler;

use MonologConfig\Handler\RotatingFileSizeHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class RotatingFileSizeHandlerTest extends TestCase
{
    const TMP_LOG_DIR  = __DIR__ . '/log';
    const TMP_LOG_FILE = self::TMP_LOG_DIR . '/test.log';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // create directory for log files
        mkdir(self::TMP_LOG_DIR);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        foreach (glob(self::TMP_LOG_DIR . '/*') as $filename) {
            unlink($filename);
        }

        rmdir(self::TMP_LOG_DIR);
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @return array
     */
    protected function getRecord($message = 'test', $level = Logger::WARNING, $context = array())
    {
        return array(
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => array(),
        );
    }

    public function testLogFileIsCreated()
    {
        $handler = new RotatingFileSizeHandler(
            self::TMP_LOG_FILE
        );

        $handler->handle($this->getRecord('test-message'));

        $this->assertFileExists(self::TMP_LOG_FILE);
    }

    public function testLogFileSizeNotExceeded()
    {
        $handler = new RotatingFileSizeHandler(
            self::TMP_LOG_DIR . '/test-unlimited-size.log',
            0
        );

        $handler->handle($this->getRecord('test-message'));
        $handler->handle($this->getRecord('test-message'));
        $handler->handle($this->getRecord('test-message'));
        $handler->handle($this->getRecord('test-message'));

        $this->assertFileExists(self::TMP_LOG_FILE);
    }

    /**
     * @depends testLogFileIsCreated
     */
    public function testLogFileSizeIsExceededAndFirstRotationFileIsCreated()
    {
        $handler = new RotatingFileSizeHandler(
            self::TMP_LOG_FILE,
            .00001
        );

        $handler->handle($this->getRecord('test-message'));
        $expectedRotationFileName = sprintf(
            '%s/test-%s.log',
            self::TMP_LOG_DIR,
            date('Ymd')
        );

        $this->assertFileExists(self::TMP_LOG_FILE);
        $this->assertFileExists($expectedRotationFileName);
    }

    /**
     * @depends testLogFileSizeIsExceededAndFirstRotationFileIsCreated
     */
    public function testLogFileFollowUpRotationCounter()
    {
        $handler = new RotatingFileSizeHandler(
            self::TMP_LOG_FILE,
            .00001
        );

        $handler->handle($this->getRecord('test-message'));

        $expectedRotationFileName = sprintf(
            '%s/test-%s-1.log',
            self::TMP_LOG_DIR,
            date('Ymd')
        );

        $this->assertFileExists(self::TMP_LOG_FILE);
        $this->assertFileExists($expectedRotationFileName);

        $handler->handle($this->getRecord('test-message'));
        $expectedRotationFileName = sprintf(
            '%s/test-%s-2.log',
            self::TMP_LOG_DIR,
            date('Ymd')
        );
        $this->assertFileExists($expectedRotationFileName);

        $handler->handle($this->getRecord('test-message'));
        $expectedRotationFileName = sprintf(
            '%s/test-%s-3.log',
            self::TMP_LOG_DIR,
            date('Ymd')
        );
        $this->assertFileExists($expectedRotationFileName);
    }

    /**
     * @depends testLogFileIsCreated
     */
    public function testLogFileWithCompressionSuffixIsCreated()
    {
        $compressionLevel = 1;

        $handler = new RotatingFileSizeHandler(
            self::TMP_LOG_FILE,
            .00001,
            $compressionLevel
        );

        $handler->handle($this->getRecord('test-message'));

        $expectedRotationFileName = sprintf(
            '%s/test-%s.log.gz',
            self::TMP_LOG_DIR,
            date('Ymd')
        );

        $this->assertFileExists(self::TMP_LOG_FILE);
        $this->assertFileExists($expectedRotationFileName);

        return [$compressionLevel, $expectedRotationFileName];
    }

    /**
     * @depends testLogFileWithCompressionSuffixIsCreated
     */
    public function testRotatedLogFileContentIsGzipCompressed(array $args)
    {
        /*
         * PHP 7.1 could use array destructuring
         * [$level, $file] = $args;
         */
        list($level, $compressedFile) = $args;

        $content = file_get_contents(self::TMP_LOG_FILE);
        $compressedContent = gzencode($content, $level);

        $this->assertEquals(
            $compressedContent,
            file_get_contents($compressedFile)
        );
    }

    /**
     * @depends testLogFileWithCompressionSuffixIsCreated
     */
    public function testLogFileFollowUpRotationCounterWithCompressionSuffix()
    {
        $compressionLevel = 1;

        $handler = new RotatingFileSizeHandler(
            self::TMP_LOG_FILE,
            .00001,
            $compressionLevel
        );

        $handler->handle($this->getRecord('test-message'));
        $expectedRotationFileName = sprintf(
            '%s/test-%s-1.log.gz',
            self::TMP_LOG_DIR,
            date('Ymd')
        );
        $this->assertFileExists($expectedRotationFileName);

        $handler->handle($this->getRecord('test-message'));
        $expectedRotationFileName = sprintf(
            '%s/test-%s-2.log.gz',
            self::TMP_LOG_DIR,
            date('Ymd')
        );
        $this->assertFileExists($expectedRotationFileName);

        $handler->handle($this->getRecord('test-message'));
        $expectedRotationFileName = sprintf(
            '%s/test-%s-3.log.gz',
            self::TMP_LOG_DIR,
            date('Ymd')
        );
        $this->assertFileExists($expectedRotationFileName);
    }
}
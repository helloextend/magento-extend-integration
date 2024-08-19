<?php

namespace Extend\Integration\Test\Unit; /* add \Path\To\Dir */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Plugin\RequireJs\AfterGetFilesPlugin;
use Magento\Framework\RequireJs\Config\File\Collector\Aggregated;
use Magento\Framework\View\File;

class AfterGetFilesPluginTest extends TestCase
{

    /**
     * @var ExtendService | MockObject
     */
    private $extendServiceMock;

    /**
     * @var AfterGetFilesPlugin
     */
    private $testSubject;

    /**
     * @var Aggregated | MockObject
     */
    private $aggregatedSubjectMock;

    /**
     * @var Magento\Framework\View\File[]
     * mock return value from Magento\Framework\RequireJs\Config\File\Collector\Aggregated::getFiles()
     */
    private $requireJsFilesMock;

    /**
     * @var Magento\Framework\View\File | MockObject
     */
    private $mockExtendFile;

    /**
     * @var Magento\Framework\View\File | MockObject
     */
    private $mockNonExtendFile;

    protected function setUp(): void
    {
        // create mock constructor arg for the tested class
        $this->extendServiceMock = $this->createStub(ExtendService::class);

        // create an instance of the class to test
        $this->testSubject = new AfterGetFilesPlugin($this->extendServiceMock);

        // create some mock files
        $this->mockExtendFile = $this->createStub(File::class);
        $this->mockExtendFile->method('getModule')->willReturn('Extend_Integration');
        $this->mockNonExtendFile = $this->createStub(File::class);
        $this->mockNonExtendFile->method('getModule')->willReturn('Some_Other_Module');

        // create a mock for the Aggregated class, needed as an argument to the plugin method being tested
        $this->aggregatedSubjectMock = $this->createMock(Aggregated::class);

        // create a mock return value from requireJS getFiles method - this is the input to the plugin method
        $this->requireJsFilesMock = [
            'extend' => $this->mockExtendFile,
            'not-extend' => $this->mockNonExtendFile,
        ];
    }

  /* =================================================================================================== */
  /* ============================================== tests ============================================== */
  /* =================================================================================================== */


    public function testDoesNotFilterOutExtendFilesWhenExtendIsEnabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(true);
        $this->expectUnfilteredFiles();
    }

    public function testFiltersOutExtendFilesWhenExtendIsDisabled()
    {
        $this->extendServiceMock->method('isEnabled')->willReturn(false);
        // expect the extend module file to be removed
        $this->expectFilteredFiles();
    }

  /* =================================================================================================== */
  /* ============================== helper methods for validating results ============================== */
  /* =================================================================================================== */

    // expect no filtering
    private function expectUnfilteredFiles()
    {
        $this->extendServiceMock->expects($this->exactly(1))->method('isEnabled');
        $result = $this->testSubject->afterGetFiles(
            $this->aggregatedSubjectMock,
            $this->requireJsFilesMock
        );
        $this->assertArrayHasKey('not-extend', $result);
        $this->assertArrayHasKey('extend', $result);
    }

    // expect filtering out of extend file
    private function expectFilteredFiles()
    {
        // expect that the envViewModelMock methods were each called once
        $this->extendServiceMock->expects($this->exactly(1))->method('isEnabled');
        $result = $this->testSubject->afterGetFiles(
            $this->aggregatedSubjectMock,
            $this->requireJsFilesMock
        );
        $this->assertArrayHasKey('not-extend', $result);
        // expect that the 'extend' key was removed
        $this->assertArrayNotHasKey('extend', $result);
    }
}

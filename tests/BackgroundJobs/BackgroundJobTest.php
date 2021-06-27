<?php

namespace OCA\WorkflowMediaConverter\Tests\BackgroundJobs;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use OCA\WorkflowMediaConverter\Service\ConfigService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

abstract class BackgroundJobTest extends TestCase
{
    protected function setUp(): void
    {
        $this->time = m::mock(ITimeFactory::class);
        $this->logger = m::spy(LoggerInterface::class);
        $this->rootFolder = m::mock(IRootFolder::class);
        $this->jobList = m::mock(IJobList::class);
        $this->configService = m::mock(ConfigService::class);

        $this->videoFolder = $this->createTestFolder('/files/admin/camera-uploads');
        $this->videoSubfolder = $this->createTestSubFolder($this->videoFolder, '/files/admin/camera-uploads/2020');
        $this->videoSubfolderNodes = [
            $this->createFile($this->videoFolder, 'test-1.mov', '/files/admin/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-2.mov', '/files/admin/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-2.avi', '/files/admin/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-3.mov', '/files/admin/camera-uploads'),
            $this->createFile($this->videoFolder, 'test-3.mp4', '/files/admin/camera-uploads'),
        ];
        $this->videoSubfolder->allows()->getDirectoryListing()->andReturns($this->videoSubfolderNodes);
        $this->videoFolderNodes = [
            $this->videoSubfolder,
            $this->createFile($this->videoFolder, 'test-1.mov', '/files/admin/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-2.mov', '/files/admin/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-2.avi', '/files/admin/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-3.mov', '/files/admin/camera-uploads/2020'),
            $this->createFile($this->videoFolder, 'test-3.mp4', '/files/admin/camera-uploads/2020'),
        ];
        $this->videoFolder->allows()->getDirectoryListing()->andReturns($this->videoFolderNodes);

        $this->sourceMoveFolder = $this->createTestFolder('/files/admin/converted/source');
        $this->outputMoveFolder = $this->createTestFolder('/files/admin/converted/output');
        $this->conflictMoveFolder = $this->createTestFolder('/files/admin/converted/conflicts');
    }

    /**
     * @param MockInterface|Folder $folder 
     * @param string $filename 
     * @param string|null $convertedFilename 
     * @return MockInterface|File
     */
    protected function createFile($folder, $filename, $folderPath = '', $convertedFilename = null)
    {
        /** @var MockInterface|File $file */
        $file = m::mock(File::class);

        $file->allows()->getName()->andReturns($filename);
        $file->allows()->getPath()->andReturns("$folderPath/$filename");
        $file->allows()->getParent()->andReturns($folder);

        if (!empty($convertedFilename)) {
            $folder->allows()->nodeExists($convertedFilename)->andReturns(false);
        }

        return $file;
    }

    /**
     * 
     * @return MockInterface|Folder
     */
    protected function createTestFolder($path)
    {
        $folder = m::mock(Folder::class);

        $this->rootFolder->allows()->get($path)->andReturns($folder);

        return $folder;
    }

    protected function createTestSubfolder($parentFolder, $path)
    {
        $subfolder = $this->createTestFolder($path);

        $subfolder->allows()->getParent()->andReturns($parentFolder);

        return $subfolder;
    }

    protected abstract function createJobArguments($overrides = []);

    protected function setJobArguments($overrides = [])
    {
        $arguments = $this->createJobArguments($overrides);

        $this->configService->expects()->setUserId($arguments['user_id'])->once();
        $this->rootFolder->expects()->get($arguments['sourceFolder'])->once();
        
        $this->job->parseArguments($arguments);

        return $arguments;
    }
}
<?php

namespace Accompli\Test\Task;

use Accompli\AccompliEvents;
use Accompli\Deployment\Connection\ConnectionAdapterInterface;
use Accompli\Deployment\Host;
use Accompli\Deployment\Release;
use Accompli\Deployment\Workspace;
use Accompli\EventDispatcher\Event\PrepareDeployReleaseEvent;
use Accompli\EventDispatcher\Event\WorkspaceEvent;
use Accompli\EventDispatcher\EventDispatcherInterface;
use Accompli\Exception\TaskRuntimeException;
use Accompli\Task\MaintenanceModeTask;
use Accompli\Utility\VersionCategoryComparator;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

/**
 * MaintenanceModeTaskTest.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class MaintenanceModeTaskTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests if MaintenanceTask::getSubscribedEvents returns an array with at least a AccompliEvents::PREPARE_WORKSPACE and AccompliEvents::PREPARE_DEPLOY_RELEASE key.
     */
    public function testGetSubscribedEvents()
    {
        $this->assertInternalType('array', MaintenanceModeTask::getSubscribedEvents());
        $this->assertArrayHasKey(AccompliEvents::PREPARE_WORKSPACE, MaintenanceModeTask::getSubscribedEvents());
        $this->assertArrayHasKey(AccompliEvents::PREPARE_DEPLOY_RELEASE, MaintenanceModeTask::getSubscribedEvents());
    }

    /**
     * Tests if constructing a new MaintenanceTask sets the instance properties.
     */
    public function testConstruct()
    {
        $task = new MaintenanceModeTask();

        $this->assertAttributeSame(VersionCategoryComparator::MATCH_MAJOR_DIFFERENCE, 'strategy', $task);
        $this->assertAttributeSame(realpath(__DIR__.'/../../src/Resources/maintenance'), 'localMaintenanceDirectory', $task);
    }

    /**
     * Tests if constructing a new MaintenanceTask with an invalid strategy throws an InvalidArgumentException.
     */
    public function testConstructThrowsInvalidArgumentException()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'The strategy type "invalid" is invalid.');

        new MaintenanceModeTask('invalid');
    }

    /**
     * Tests if MaintenanceTask::onPrepareWorkspaceUploadMaintenancePage calls the connection adapter to create and upload the maintenance page in the workspace.
     */
    public function testOnPrepareWorkspaceUploadMaintenancePage()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->atLeast(4))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->once())
                ->method('isConnected')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->exactly(2))
                ->method('isDirectory')
                ->willReturnOnConsecutiveCalls(false, true);
        $connectionAdapterMock->expects($this->once())
                ->method('createDirectory')
                ->with('{workspace}/maintenance/')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->atLeastOnce())
                ->method('putFile')
                ->willReturn(true);

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->once())
                ->method('hasConnection')
                ->willReturn(true);
        $hostMock->expects($this->once())
                ->method('getConnection')
                ->willReturn($connectionAdapterMock);
        $hostMock->expects($this->once())
                ->method('getPath')
                ->willReturn('{workspace}');

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->exactly(1))
                ->method('getHost')
                ->willReturn($hostMock);

        $eventMock = $this->getMockBuilder(WorkspaceEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->exactly(1))
                ->method('getWorkspace')
                ->willReturn($workspaceMock);

        $task = new MaintenanceModeTask();
        $task->onPrepareWorkspaceUploadMaintenancePage($eventMock, AccompliEvents::PREPARE_WORKSPACE, $eventDispatcherMock);
    }

    /**
     * Tests if MaintenanceTask::onPrepareWorkspaceUploadMaintenancePage calls the connection adapter to create and upload the maintenance page in the workspace to a subdirectory of the maintenance directory.
     */
    public function testOnPrepareWorkspaceUploadMaintenancePageToDocumentRootSubdirectory()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->atLeast(4))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->once())
                ->method('isConnected')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->exactly(2))
                ->method('isDirectory')
                ->willReturnOnConsecutiveCalls(false, true);
        $connectionAdapterMock->expects($this->once())
                ->method('createDirectory')
                ->with('{workspace}/maintenance/web')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->atLeastOnce())
                ->method('putFile')
                ->with($this->anything(), $this->stringStartsWith('{workspace}/maintenance/web/'))
                ->willReturn(true);

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->once())
                ->method('hasConnection')
                ->willReturn(true);
        $hostMock->expects($this->once())
                ->method('getConnection')
                ->willReturn($connectionAdapterMock);
        $hostMock->expects($this->once())
                ->method('getPath')
                ->willReturn('{workspace}');

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->exactly(1))
                ->method('getHost')
                ->willReturn($hostMock);

        $eventMock = $this->getMockBuilder(WorkspaceEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->exactly(1))
                ->method('getWorkspace')
                ->willReturn($workspaceMock);

        $task = new MaintenanceModeTask(VersionCategoryComparator::MATCH_MAJOR_DIFFERENCE, 'web');
        $task->onPrepareWorkspaceUploadMaintenancePage($eventMock, AccompliEvents::PREPARE_WORKSPACE, $eventDispatcherMock);
    }

    /**
     * Tests if MaintenanceTask::onPrepareWorkspaceUploadMaintenancePage calls the connection adapter to upload the maintenance page in the workspace.
     */
    public function testOnPrepareWorkspaceUploadMaintenancePageWhenMaintenanceExists()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->atLeast(4))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->once())
                ->method('isConnected')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->exactly(2))
                ->method('isDirectory')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->never())
                ->method('createDirectory');
        $connectionAdapterMock->expects($this->atLeastOnce())
                ->method('putFile')
                ->willReturn(true);

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->once())
                ->method('hasConnection')
                ->willReturn(true);
        $hostMock->expects($this->once())
                ->method('getConnection')
                ->willReturn($connectionAdapterMock);

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->exactly(1))
                ->method('getHost')
                ->willReturn($hostMock);

        $eventMock = $this->getMockBuilder(WorkspaceEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->exactly(1))
                ->method('getWorkspace')
                ->willReturn($workspaceMock);

        $task = new MaintenanceModeTask();
        $task->onPrepareWorkspaceUploadMaintenancePage($eventMock, AccompliEvents::PREPARE_WORKSPACE, $eventDispatcherMock);
    }

    /**
     * Tests if MaintenanceTask::onPrepareWorkspaceUploadMaintenancePage calls the connection adapter when creating the maintenance page directory.
     */
    public function testOnPrepareWorkspaceUploadMaintenancePageFailure()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->exactly(2))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->once())
                ->method('isConnected')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->exactly(2))
                ->method('isDirectory')
                ->willReturn(false);
        $connectionAdapterMock->expects($this->once())
                ->method('createDirectory')
                ->willReturn(false);
        $connectionAdapterMock->expects($this->never())
                ->method('putFile')
                ->willReturn(true);

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->once())
                ->method('hasConnection')
                ->willReturn(true);
        $hostMock->expects($this->once())
                ->method('getConnection')
                ->willReturn($connectionAdapterMock);
        $hostMock->expects($this->once())
                ->method('getPath')
                ->willReturn('{workspace}');

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->exactly(1))
                ->method('getHost')
                ->willReturn($hostMock);

        $eventMock = $this->getMockBuilder(WorkspaceEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->exactly(1))
                ->method('getWorkspace')
                ->willReturn($workspaceMock);

        $task = new MaintenanceModeTask();
        $task->onPrepareWorkspaceUploadMaintenancePage($eventMock, AccompliEvents::PREPARE_WORKSPACE, $eventDispatcherMock);
    }

    /**
     * Tests if MaintenanceTask::onPrepareDeployReleaseLinkMaintenancePageToStage calls the connection adapter to link the stage to the maintenance directory.
     */
    public function testOnPrepareDeployReleaseLinkMaintenancePageToStage()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->exactly(2))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->once())
                ->method('isConnected')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->once())
                ->method('isLink')
                ->willReturn(false);
        $connectionAdapterMock->expects($this->once())
                ->method('link')
                ->with('{workspace}/maintenance/', '{workspace}/test')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->never())
                ->method('delete');

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->once())
                ->method('hasConnection')
                ->willReturn(true);
        $hostMock->expects($this->once())
                ->method('getConnection')
                ->willReturn($connectionAdapterMock);
        $hostMock->expects($this->once())
                ->method('getStage')
                ->willReturn('test');
        $hostMock->expects($this->exactly(2))
                ->method('getPath')
                ->willReturn('{workspace}');

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->once())
                ->method('getHost')
                ->willReturn($hostMock);

        $releaseMock = $this->getMockBuilder(Release::class)
                ->disableOriginalConstructor()
                ->getMock();

        $eventMock = $this->getMockBuilder(PrepareDeployReleaseEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->once())
                ->method('getWorkspace')
                ->willReturn($workspaceMock);
        $eventMock->expects($this->once())
                ->method('getRelease')
                ->willReturn($releaseMock);

        $task = new MaintenanceModeTask();
        $task->onPrepareDeployReleaseLinkMaintenancePageToStage($eventMock, AccompliEvents::PREPARE_DEPLOY_RELEASE, $eventDispatcherMock);
    }

    /**
     * Tests if MaintenanceTask::onPrepareDeployReleaseLinkMaintenancePageToStage calls the connection adapter to unlink an existing stage link and link the stage to the maintenance directory.
     */
    public function testOnPrepareDeployReleaseLinkMaintenancePageToStageWhenStageLinkExists()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->exactly(2))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->once())
                ->method('isConnected')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->once())
                ->method('isLink')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->once())
                ->method('link')
                ->with('{workspace}/maintenance/', '{workspace}/test')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->once())
                ->method('delete')
                ->with('{workspace}/test', false)
                ->willReturn(true);

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->once())
                ->method('hasConnection')
                ->willReturn(true);
        $hostMock->expects($this->once())
                ->method('getConnection')
                ->willReturn($connectionAdapterMock);
        $hostMock->expects($this->once())
                ->method('getStage')
                ->willReturn('test');
        $hostMock->expects($this->exactly(2))
                ->method('getPath')
                ->willReturn('{workspace}');

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->once())
                ->method('getHost')
                ->willReturn($hostMock);

        $releaseMock = $this->getMockBuilder(Release::class)
                ->disableOriginalConstructor()
                ->getMock();

        $eventMock = $this->getMockBuilder(PrepareDeployReleaseEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->once())
                ->method('getWorkspace')
                ->willReturn($workspaceMock);
        $eventMock->expects($this->once())
                ->method('getRelease')
                ->willReturn($releaseMock);

        $task = new MaintenanceModeTask();
        $task->onPrepareDeployReleaseLinkMaintenancePageToStage($eventMock, AccompliEvents::PREPARE_DEPLOY_RELEASE, $eventDispatcherMock);
    }

    /**
     * Tests if MaintenanceTask::onPrepareDeployReleaseLinkMaintenancePageToStage calls the connection adapter to unlink an existing stage link and link the stage to the maintenance directory.
     */
    public function testOnPrepareDeployReleaseLinkMaintenancePageToStageDoesNotExecuteWhenVersionCategoryDifferenceDoesNotMatchStrategy()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->exactly(1))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->never())
                ->method('isConnected');
        $connectionAdapterMock->expects($this->never())
                ->method('isLink');
        $connectionAdapterMock->expects($this->never())
                ->method('link');
        $connectionAdapterMock->expects($this->never())
                ->method('delete');

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->never())
                ->method('hasConnection');
        $hostMock->expects($this->never())
                ->method('getConnection');
        $hostMock->expects($this->never())
                ->method('getStage');

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->never())
                ->method('getHost');

        $releaseMock = $this->getMockBuilder(Release::class)
                ->disableOriginalConstructor()
                ->getMock();
        $releaseMock->expects($this->exactly(2))
                ->method('getVersion')
                ->willReturn('0.1.0');

        $eventMock = $this->getMockBuilder(PrepareDeployReleaseEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->never())
                ->method('getWorkspace');
        $eventMock->expects($this->once())
                ->method('getRelease')
                ->willReturn($releaseMock);
        $eventMock->expects($this->once())
                ->method('getCurrentRelease')
                ->willReturn($releaseMock);

        $task = new MaintenanceModeTask();
        $task->onPrepareDeployReleaseLinkMaintenancePageToStage($eventMock, AccompliEvents::PREPARE_DEPLOY_RELEASE, $eventDispatcherMock);
    }

    /**
     * Tests if MaintenanceTask::onPrepareDeployReleaseLinkMaintenancePageToStage throws a RuntimeException when the connection adapter fails to link the stage to the maintenance directory.
     */
    public function testOnPrepareDeployReleaseLinkMaintenancePageToStageFailure()
    {
        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)
                ->getMock();
        $eventDispatcherMock->expects($this->exactly(3))
                ->method('dispatch');

        $connectionAdapterMock = $this->getMockBuilder(ConnectionAdapterInterface::class)
                ->getMock();
        $connectionAdapterMock->expects($this->once())
                ->method('isConnected')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->once())
                ->method('isLink')
                ->willReturn(true);
        $connectionAdapterMock->expects($this->once())
                ->method('delete')
                ->willReturn(false);
        $connectionAdapterMock->expects($this->once())
                ->method('link')
                ->with('/maintenance/', '/test')
                ->willReturn(false);

        $hostMock = $this->getMockBuilder(Host::class)
                ->disableOriginalConstructor()
                ->getMock();
        $hostMock->expects($this->once())
                ->method('hasConnection')
                ->willReturn(true);
        $hostMock->expects($this->once())
                ->method('getConnection')
                ->willReturn($connectionAdapterMock);
        $hostMock->expects($this->once())
                ->method('getStage')
                ->willReturn('test');

        $workspaceMock = $this->getMockBuilder(Workspace::class)
                ->disableOriginalConstructor()
                ->getMock();
        $workspaceMock->expects($this->once())
                ->method('getHost')
                ->willReturn($hostMock);

        $releaseMock = $this->getMockBuilder(Release::class)
                ->disableOriginalConstructor()
                ->getMock();

        $eventMock = $this->getMockBuilder(PrepareDeployReleaseEvent::class)
                ->disableOriginalConstructor()
                ->getMock();
        $eventMock->expects($this->once())
                ->method('getWorkspace')
                ->willReturn($workspaceMock);
        $eventMock->expects($this->once())
                ->method('getRelease')
                ->willReturn($releaseMock);

        $this->setExpectedException(TaskRuntimeException::class, 'Linking "/test" to maintenance page failed.');

        $task = new MaintenanceModeTask();
        $task->onPrepareDeployReleaseLinkMaintenancePageToStage($eventMock, AccompliEvents::PREPARE_DEPLOY_RELEASE, $eventDispatcherMock);
    }
}

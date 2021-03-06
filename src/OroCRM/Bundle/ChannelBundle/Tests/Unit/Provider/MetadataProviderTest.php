<?php

namespace OroCRM\Bundle\ChannelBundle\Tests\Unit\Provider;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

use OroCRM\Bundle\ChannelBundle\Provider\MetadataProvider;
use OroCRM\Bundle\ChannelBundle\Provider\SettingsProvider;

class MetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var int */
    protected $entityId1 = 35;

    /** @var int */
    protected $entityId2 = 84;

    /** @var array */
    protected $testConfig = [
        'OroCRM\Bundle\TestBundle1\Entity\Entity1' => [
            'name'                   => 'OroCRM\Bundle\TestBundle1\Entity\Entity1',
            'dependent'              => [],
            'navigation_items'       => [],
            'dependencies'           => [],
            'dependencies_condition' => 'OR',
            'belongs_to'             => ['integration' => 'testIntegrationType']
        ],
        'OroCRM\Bundle\TestBundle1\Entity\Entity2' => [
            'name'                   => 'OroCRM\Bundle\TestBundle2\Entity\Entity2',
            'dependent'              => [],
            'navigation_items'       => [],
            'dependencies'           => [],
            'dependencies_condition' => 'AND',
            'belongs_to'             => ['integration' => 'testIntegrationType']
        ],
        'OroCRM\Bundle\TestBundle2\Entity\Entity3' => [
            'name'                   => 'OroCRM\Bundle\TestBundle2\Entity\Entity3',
            'dependent'              => [],
            'navigation_items'       => [],
            'dependencies'           => [],
            'dependencies_condition' => 'AND',
        ],
    ];

    /** @var array */
    protected $entityConfig1 = [
        'name'         => 'OroCRM\Bundle\TestBundle1\Entity\Entity1',
        'label'        => 'Entity 1',
        'plural_label' => 'Entities 1',
        'icon'         => '',
    ];

    /** @var array */
    protected $entityConfig2 = [
        'name'         => 'OroCRM\Bundle\TestBundle2\Entity\Entity2',
        'label'        => 'Entity 2',
        'plural_label' => 'Entities 2',
        'icon'         => '',
    ];

    /** @var array */
    protected $entityConfig3 = [
        'name'         => 'OroCRM\Bundle\TestBundle2\Entity\Entity3',
        'label'        => 'Entity 3',
        'plural_label' => 'Entities 3',
        'icon'         => '',
    ];

    /** @var SettingsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $settingsProvider;

    /** @var  EntityProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityProvider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var EntityConfigModel|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigModel1;

    /** @var EntityConfigModel|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigModel2;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    public function setUp()
    {
        $this->settingsProvider   = $this->getMockBuilder('OroCRM\Bundle\ChannelBundle\Provider\SettingsProvider')
            ->disableOriginalConstructor()->getMock();
        $this->settingsProvider->expects($this->once())
            ->method('getSettings')
            ->will($this->returnvalue($this->testConfig));

        $this->entityProvider     = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()->getMock();
        $this->configManager      = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->entityConfigModel1 = $this->getMock('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel');
        $this->entityConfigModel2 = $this->getMock('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel');

        $this->entityConfigModel1->expects($this->any())
            ->method('getId')
            ->will($this->returnvalue($this->entityId1));

        $this->entityConfigModel2->expects($this->any())
            ->method('getId')
            ->will($this->returnvalue($this->entityId2));

        $this->router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->disableOriginalConstructor()->getMock();
    }

    public function tearDown()
    {
        unset(
            $this->router,
            $this->entityProvider,
            $this->configManager,
            $this->settingsProvider,
            $this->entityConfigModel1,
            $this->entityConfigModel2
        );
    }

    public function testGetEntitiesMetadata()
    {
        $this->entityProvider->expects($this->at(0))
            ->method('getEntity')
            ->will($this->returnvalue($this->entityConfig1));
        $this->entityProvider->expects($this->at(1))
            ->method('getEntity')
            ->will($this->returnvalue($this->entityConfig2));
        $this->entityProvider->expects($this->at(2))
            ->method('getEntity')
            ->will($this->returnvalue($this->entityConfig3));

        $extendConfigModel = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $extendConfigModel->expects($this->any())
            ->method('get')
            ->with($this->equalTo('owner'))
            ->will($this->returnValue('Custom'));

        $extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $extendProvider->expects($this->once())
            ->method('map')
            ->will($this->returnValue([]));
        $extendProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($extendConfigModel));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with($this->equalTo('extend'))
            ->will($this->returnValue($extendProvider));
        $this->configManager->expects($this->any())
            ->method('getConfigEntityModel')
            ->will($this->onConsecutiveCalls($this->entityConfigModel1, $this->entityConfigModel2));

        $this->router->expects($this->exactly(4))
            ->method('generate');

        /** @var MetadataProvider $provider */
        $provider = new MetadataProvider(
            $this->settingsProvider,
            $this->entityProvider,
            $this->configManager,
            $this->router
        );

        $result = $provider->getEntitiesMetadata();
        for ($i = 1; $i < 3; $i++) {
            $expectedConfig = $this->getExpectedConfig($i);
            $entityName     = $expectedConfig['name'];

            $this->assertEquals($expectedConfig, $result[$entityName]);
        }
    }

    /**
     * @param $index
     *
     * @return array
     */
    protected function getExpectedConfig($index)
    {
        $configName          = 'entityConfig' . $index;
        $entityId            = 'entityId' . $index;
        $result              = $this->$configName;
        $result['entity_id'] = $this->$entityId;
        $result['edit_link'] = null;
        $result['view_link'] = null;
        $result['type']      = 'Custom';

        return $result;
    }
}

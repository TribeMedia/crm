<?php

namespace OroCRM\Bundle\MagentoBundle\Tests\Functional\Controller;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use OroCRM\Bundle\MagentoBundle\Entity\NewsletterSubscriber;

/**
 * @dbIsolation
 */
class NewsletterSubscriberControllerTest extends AbstractController
{
    /**
     * @var NewsletterSubscriber
     */
    protected $subscriber;

    /**
     * @var JobExecutor
     */
    protected $baseJobExecutor;

    /**
     * {@inheritdoc}
     */
    protected function getMainEntityId()
    {
        return $this->subscriber->getid();
    }

    protected function setUp()
    {
        $this->initClient(['debug' => false], $this->generateBasicAuthHeader(), true);

        $this->loadFixtures(
            ['OroCRM\Bundle\MagentoBundle\Tests\Functional\Fixture\LoadNewsletterSubscriberData'],
            true
        );

        $this->subscriber = $this->getReference('newsletter_subscriber');

        $this->baseJobExecutor = $this->getContainer()->get('oro_importexport.job_executor');

        $jobExecutor = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Job\JobExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $jobResult = new JobResult();
        $jobResult->setSuccessful(true);

        $jobExecutor->expects($this->any())
            ->method('executeJob')
            ->willReturn($jobResult);

        $this->getContainer()->set('oro_importexport.job_executor', $jobExecutor);
    }

    protected function tearDown()
    {
        // clear DB from separate connection
        $batchJobManager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobInstance')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobExecution')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:StepExecution')->execute();

        $this->getContainer()->set('oro_importexport.job_executor', $this->baseJobExecutor);
        unset($this->transport, $this->baseJobExecutor);
    }

    /**
     * @param array $requestData
     *
     * @dataProvider gridProvider
     */
    public function testGrid($requestData)
    {
        parent::testGrid($requestData);
    }

    /**
     * @depends testGrid
     */
    public function testView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('orocrm_magento_newsletter_subscriber_view', ['id' => $this->getMainEntityId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('General Information', $result->getContent());
        $this->assertContains($this->subscriber->getCustomer()->getFirstName(), $result->getContent());
        $this->assertContains($this->subscriber->getCustomer()->getLastName(), $result->getContent());
        $this->assertContains($this->subscriber->getEmail(), $result->getContent());
        $this->assertContains($this->subscriber->getStatus()->getName(), $result->getContent());
    }

    /**
     * @return array
     */
    public function gridProvider()
    {
        return [
            'default' => [
                [
                    'gridParameters' => ['gridName' => 'magento-newsletter-subscriber-grid'],
                    'gridFilters' => [],
                    'assert' => [
                        'channelName' => 'Magento channel',
                        'email' => 'subscriber@example.com',
                        'status' => 'Subscribed',
                        'customerName' => 'John Doe',
                        'customerEmail' => 'test@example.com'
                    ],
                    'expectedResultCount' => 2
                ]
            ],
            'filters' => [
                [
                    'gridParameters' => ['gridName' => 'magento-newsletter-subscriber-grid'],
                    'gridFilters' => [
                        'magento-newsletter-subscriber-grid[_filter][status][value]' => '1'
                    ],
                    'assert' => [
                        'channelName' => 'Magento channel',
                        'email' => 'subscriber@example.com',
                        'status' => 'Subscribed',
                        'customerName' => 'John Doe',
                        'customerEmail' => 'test@example.com'
                    ],
                    'expectedResultCount' => 2
                ]
            ],
            'no result' => [
                [
                    'gridParameters' => ['gridName' => 'magento-newsletter-subscriber-grid'],
                    'gridFilters' => [
                        'magento-newsletter-subscriber-grid[_filter][email][value]' => 'not.exists@example.com'
                    ],
                    'assert' => [],
                    'expectedResultCount' => 0
                ]
            ]
        ];
    }

    /**
     * @depends testView
     */
    public function testUnsubscribe()
    {
        $this->client->request(
            'GET',
            $this->getUrl('orocrm_magento_newsletter_subscriber_unsubscribe', ['id' => $this->getMainEntityId()])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $result['state']);
    }

    /**
     * @depends testUnsubscribe
     */
    public function testSubscribe()
    {
        $this->client->request(
            'GET',
            $this->getUrl('orocrm_magento_newsletter_subscriber_subscribe', ['id' => $this->getMainEntityId()])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(NewsletterSubscriber::STATUS_SUBSCRIBED, $result['state']);
    }

    /**
     * @depends testSubscribe
     */
    public function testUnsubscribeByCustomer()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'orocrm_magento_newsletter_subscriber_unsubscribe_customer',
                ['id' => $this->subscriber->getCustomer()->getId()]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(NewsletterSubscriber::STATUS_UNSUBSCRIBED, $result['state']);
    }

    /**
     * @depends testUnsubscribeByCustomer
     */
    public function testSubscribeByCustomer()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'orocrm_magento_newsletter_subscriber_subscribe_customer',
                ['id' => $this->subscriber->getCustomer()->getId()]
            )
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(NewsletterSubscriber::STATUS_SUBSCRIBED, $result['state']);
    }
}

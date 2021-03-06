<?php

namespace OroCRM\Bundle\MagentoBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use OroCRM\Bundle\MagentoBundle\Entity\NewsletterSubscriber;

class NewsletterSubscriberManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Customer $customer
     *
     * @return NewsletterSubscriber
     */
    public function getOrCreateFromCustomer(Customer $customer)
    {
        $newsletterSubscriber = $customer->getNewsletterSubscriber();

        if (null === $newsletterSubscriber) {
            $newsletterSubscriber = new NewsletterSubscriber();
            $newsletterSubscriber
                ->setCustomer($customer)
                ->setEmail($customer->getEmail())
                ->setChannel($customer->getChannel())
                ->setStore($customer->getStore())
                ->setOrganization($customer->getOrganization())
                ->setOwner($customer->getOwner())
                ->setStatus($this->getStatus(NewsletterSubscriber::STATUS_UNSUBSCRIBED))
                ->setDataChannel($customer->getDataChannel());
        }

        return $newsletterSubscriber;
    }

    /**
     * @param int $identifier
     *
     * @return AbstractEnumValue
     */
    protected function getStatus($identifier)
    {
        $className = ExtendHelper::buildEnumValueClassName('mage_subscr_status');

        return $this->doctrineHelper->getEntityRepository($className)->find($identifier);
    }
}

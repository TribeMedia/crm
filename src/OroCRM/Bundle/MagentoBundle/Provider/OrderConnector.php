<?php

namespace OroCRM\Bundle\MagentoBundle\Provider;

class OrderConnector extends AbstractMagentoConnector
{
    const IMPORT_JOB_NAME = 'mage_order_import';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.magento.connector.order.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return self::ORDER_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getOrders();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsForceSync()
    {
        return true;
    }
}

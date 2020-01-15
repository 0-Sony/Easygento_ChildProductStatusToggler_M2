<?php
/**
 * @license All Rights Reserved
 * @author Phuong LE <sony@menincode.com>
 * @copyright Copyright (c) 2019 Men In Code Ltd (https://www.menincode.com)
 */

namespace Easygento\ChildProductStatusToggler\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Message\ManagerInterface;

class AfterProductSave implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getData('product');


        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $_children = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($_children as $child) {
                $child->setStatus($product->getStatus());
                $child->save();
            }
            $message = $product->getStatus() == Status::STATUS_DISABLED
                ? __('The child products associated have been disabled')
                : __('The child products associated have been enabled');

            $this->messageManager->addSuccessMessage($message);
        }
    }
}

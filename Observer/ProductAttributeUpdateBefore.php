<?php
/**
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author Phuong LE <sony@menincode.com>
 * @copyright Copyright (c) 2019 Men In Code Ltd (https://www.menincode.com)
 */

namespace Easygento\ChildProductStatusToggler\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class ProductAttributeUpdateBefore implements ObserverInterface
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * ProductAttributeUpdateBefore constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        ManagerInterface $messageManager
    ) {
        $this->productRepository = $productRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $productIds = $observer->getData('product_ids');
        $attributesData = $observer->getData('attributes_data');
        $statusIsUpdated = array_key_exists('status', $attributesData);

        if ($statusIsUpdated) {
            foreach ($productIds as $id) {
                $product = $this->productRepository->getById($id);
                if ($product->getTypeId() == Configurable::TYPE_CODE) {
                    $_children = $product->getTypeInstance()->getUsedProducts($product);
                    /** @var Product $child */
                    foreach ($_children as $child) {
                        $child->setStatus($attributesData['status']);
                        $child->save();
                    }
                    $message = $product->getStatus() == Status::STATUS_DISABLED
                        ? __('The child products associated have been disabled')
                        : __('The child products associated have been enabled');

                    $this->messageManager->addSuccessMessage($message);
                }
            }
        }
    }
}

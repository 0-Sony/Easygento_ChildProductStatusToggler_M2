<?php
/**
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author Phuong LE <sony@menincode.com>
 * @copyright Copyright (c) 2019 Men In Code Ltd (https://www.menincode.com)
 */

namespace Easygento\ChildProductStatusToggler\Observer;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Message\ManagerInterface;

class AfterProductSave implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * AfterProductSave constructor.
     * @param ManagerInterface $messageManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ManagerInterface $messageManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->messageManager = $messageManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getData('product');

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $_children = $product->getTypeInstance()->getUsedProducts($product);
            /** @var Product $child */
            foreach ($product->getStoreIds() as $storeId) {
                foreach ($_children as $child) {

                    /** @var ProductInterface $childProduct */
                    $childProduct = $this->productRepository->getById($child->getId());
                    $childProduct->setStatus($product->getStatus());
                    $childProduct->setStoreId($storeId);

                    try {
                        $this->productRepository->save($childProduct);

                    } catch (CouldNotSaveException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    } catch (InputException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    } catch (StateException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    }
                }
            }
            $message = $product->getStatus() == Status::STATUS_DISABLED
                ? __('The child products associated have been disabled')
                : __('The child products associated have been enabled');

            $this->messageManager->addSuccessMessage($message);
        }
    }
}

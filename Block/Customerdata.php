<?php
namespace Superb\Recommend\Block;

use Magento\Customer\CustomerData\SectionSourceInterface;

class Customerdata implements SectionSourceInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customer;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Superb\Recommend\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Customerdata constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customer
     * @param \Superb\Recommend\Helper\Data $helper
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customer,
        \Superb\Recommend\Helper\Data $helper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->customer = $customer;
        $this->encryptor = $encryptor;
        $this->helper        = $helper;
        $this->storeManager  = $storeManager;

    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData()
    {
        $customer = $this->customer;

        $data=[];
        if($customer->isLoggedIn()) {
            $data['customerId'] = hash_hmac('sha256', $customer->getId(), $this->helper->getHashSecretKey($this->storeManager->getStore()->getId()));
        }else{
            $data['customerId'] = null;
        }

        return $data;
    }
}
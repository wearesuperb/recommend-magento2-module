<?php

namespace Superb\Recommend\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\SalesRule\Api\Data\RuleInterface;

class SendCustomerCoupon
{
    private $helperData;
    private $connection;
    private $logger;
    private $groupCollectionFactory;
    private $timezone;
    private $customerRepository;
    private $couponRepository;
    private $random;
    private $apiHelper;
    private $dataHelper;
    private $ruleModel;
    private $moduleManager;
    private $ruleRepository;

    public function __construct(
        \Superb\Recommend\Helper\Data $helperData,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Superb\Recommend\Logger\Logger $logger,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepository,
        \Magento\Framework\Math\Random $random,
        \Superb\Recommend\Helper\Api $apiHelper,
        \Superb\Recommend\Helper\Data $dataHelper,
        \Magento\SalesRule\Model\RuleFactory $ruleModel,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->helperData = $helperData;
        $this->connection = $resourceConnection->getConnection();
        $this->logger = $logger;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->timezone = $timezone;
        $this->customerRepository = $customerRepository;
        $this->couponRepository = $couponRepository;
        $this->ruleRepository = $ruleRepository;
        $this->random = $random;
        $this->apiHelper = $apiHelper;
        $this->dataHelper = $dataHelper;
        $this->ruleModel = $ruleModel;
        $this->moduleManager = $moduleManager;
    }

    public function execute() {
        $websiteList = $this->helperData->getWebsitesList();

        /** @var \Magento\Store\Model\Website $website */
        foreach ($websiteList as $website) {
            if (!$this->helperData->isEnabled($website->getDefaultStore()->getId())) {
                continue;
            }
            if (!$this->helperData->isStatusCronPromoDobEnabled($website->getDefaultStore()->getId())) {
                continue;
            }
            $customerList = $this->getCustomersByDob($website->getId());
            if (count($customerList) > 0) {
                $this->createCoupon($customerList, $website);
                unset($customerList);
            }
        }
    }

    private function getCustomersByDob(?int $websiteId = null): array
    {
        try {
            $timezone = $this->timezone->getConfigTimezone(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
            $currentDate = new \DateTime('now', new \DateTimeZone($timezone));
            $select = $this->connection->select()->from(
                ['ce' => $this->connection->getTableName('customer_entity')],
                [
                    'customer_id' => 'ce.entity_id',
                    'dob' => 'ce.dob',
                    'email' => 'ce.email'
                ]
            )->where(
                'ce.is_active = 1'
            )->where(
                'ce.dob IS NOT NULL'
            )->where('ce.dob LIKE (?)', '%-' . $currentDate->format('m-d') . '%');

            if (!is_null($websiteId)) {
                $select->where('ce.website_id = ?', $websiteId);
            }

            if ($data = $this->connection->query($select)->fetchAll()) {
                return $data;
            }
            return [];
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->logger->critical($exception->getTraceAsString());
            return [];
        }
    }

    private function createCoupon(array $customerList, \Magento\Store\Model\Website $website)
    {
        $customerListWithCouponCode = [];
        foreach ($customerList as $customer) {
            try {
                $groupCustomers = $this->groupCollectionFactory->create()->getAllIds();
                $ruleModel = $this->ruleModel->create();
                $ruleModel->setName(__('Coupon code for customer #%1', $customer['email']))
                    ->setDescription(__('Birthday Discount'))
                    ->setIsAdvanced(true)
                    ->setStopRulesProcessing(false)
                    ->setDiscountQty(0)
                    ->setCustomerGroupIds($groupCustomers)
                    ->setWebsiteIds([$website->getId()])
                    ->setIsRss(0)
                    ->setUsesPerCoupon(1)
                    ->setUsesPerCustomer(1)
                    ->setCouponType(\Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC)
                    ->setSortOrder(0)
                    ->setSimpleAction(RuleInterface::DISCOUNT_ACTION_BY_PERCENT)
                    ->setDiscountAmount(10)
                    ->setApplyToShipping(0)
                    ->setFromDate($this->timezone->date()->format('Y-m-d'))
                    ->setToDate($this->timezone->date()->add(new \DateInterval('P7D'))->format('Y-m-d'))
                    ->setIsActive(true);

                /**
                 * For a special module that makes certain changes to the table salesrule
                 */
                if ($this->moduleManager->isEnabled('Webkul_SpecialPromotions')) {
                    $ruleModel->setData('wkrulesrule', '0')
                        ->setData('wkrulesrule_nqty', '0')
                        ->setData('wkrulesrule_skip_rule', '0')
                        ->setData('max_discount', '')
                        ->setData('promo_cats', '')
                        ->setData('promo_skus', '')
                        ->setData('n_threshold', '');
                }

                $rule = $ruleModel->save();

                if ($rule->getRuleId()) {
                    $couponCode = $this->generateCouponCode();
                    if ($couponCode !== '') {
                        $customer['coupon_code'] = $couponCode;
                        $customerListWithCouponCode[] = $customer;
                        $couponModel = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\SalesRule\Api\Data\CouponInterface::class);
                        $couponModel->setCode($couponCode)
                            ->setIsPrimary(1)
                            ->setRuleId($rule->getRuleId());
                        try {
                            $this->couponRepository->save($couponModel);
                        } catch (LocalizedException $exception) {
                            $this->logger->critical($exception->getMessage());
                            $this->logger->critical($exception->getTraceAsString());
                        }
                    } else {
                        $this->logger->critical(sprintf("Failed to create coupon for customer #%s", $customer['email']));
                        $this->ruleRepository->deleteById($rule->getRuleId());
                    }
                }
            } catch (\Exception | \Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->logger->critical($exception->getMessage());
                $this->logger->critical($exception->getTraceAsString());
            }
        }
        $this->resyncCustomers($customerListWithCouponCode, $website);
    }

    private function resyncCustomers(array $customersList, \Magento\Store\Model\Website $website)
    {
        try {
            $customerAttributes = $this->apiHelper->getCustomerAttributes($website->getCode());
            $customCustomerAttribute = $this->dataHelper->getCustomCustomerAttributes();
            $this->apiHelper->compareCustomAttributes($website->getCode(), $customCustomerAttribute);

            foreach ($customersList as $item) {
                $customer = $this->customerRepository->getById($item['customer_id']);
                $attributes = [];
                foreach($customerAttributes as $attribute){
                    $attributes[] = [
                        'code' => $attribute['magento_attribute'],
                        'value' => $customer->getData($attribute['magento_attribute'])
                    ];
                }

                foreach ($customCustomerAttribute as $customAttribute) {
                    if ($customAttribute['code'] == 'coupon_code') {
                        $attributes[] = [
                            'code' => $customAttribute['code'],
                            'value' => $item['coupon_code']
                        ];
                    }
                }

                $userData = [];
                $userData[] = [
                    'action' => 'upsert_update',
                    'data' => [
                        'customer_id' => $customer->getId(),
                        'email' => $customer->getEmail(),
                        'store_code' => $website->getCode(),
                        'currency' => $website->getBaseCurrencyCode(),
                        'environment' => $website->getDefaultStore()->getCode(),
                        'price_list' => 'default',
                        'register_date' => strtotime($customer->getCreatedAt()),
                        'first_name' => $customer->getFirstname(),
                        'last_name' => $customer->getLastname(),
                        'date_of_birth' => strtotime($customer->getDob()),
                        'attributes' => $attributes,
                        'event_time' => strtotime($customer->getUpdatedAt())
                    ]
                ];
                $this->apiHelper->sendCustomer($userData, $website->getCode());
            }
        } catch (LocalizedException $exception) {
            $this->logger->critical($exception->getMessage());
            $this->logger->critical($exception->getTraceAsString());
        }
    }

    private function generateCouponCode(): string
    {
        $couponCode = '';
        try {
            $prefix = 'BD-';
            $steep = 0;
            do {
                $randomString = $this->random->getRandomString(6);
                $tempCode = $prefix . $randomString;
                if ($this->couponIsExist($tempCode)) {
                    $steep++;
                    $this->logger->info("Steep: " . (string) $steep . " Code: " . $tempCode);
                } else {
                    $couponCode = $tempCode;
                    break;
                }
            } while ($steep <= 4);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getTraceAsString());
            $this->logger->critical($e->getMessage());
            return $couponCode;
        }
        return $couponCode;
    }

    private function couponIsExist(string $couponCode): bool
    {
        $couponModel = \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\SalesRule\Model\Coupon::class);
        $coupon = $couponModel->loadByCode($couponCode);
        if (empty($coupon->getRuleId())) {
            return false;
        }
        $this->logger->info("Rule ID: " . $coupon->getRuleId());
        return true;
    }
}

<?php

namespace Superb\Recommend\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;


class Index extends Action implements CsrfAwareActionInterface, HttpPostActionInterface, HttpGetActionInterface
{
    protected $logger;
    protected $dataHelper;
    protected $storeManagerInterface;
    protected $subscriberResource;

    public function __construct(
        Context $context,
        \Superb\Recommend\Logger\Logger $logger,
        \Superb\Recommend\Helper\Data $dataHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Newsletter\Model\ResourceModel\Subscriber $subscriberResource
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->subscriberResource = $subscriberResource;
    }

    public function execute()
    {
        $responseData = [
            'response' => 'accepted'
        ];

        $content = $this->_request->getContent();
        $this->logger->info($content);
        $data = @json_decode($content, 1);
        if ($this->isValidRequest($data)) {
            try {
                $subscriberData = $this->subscriberResource->loadByEmail($data['data']['identifier']);
                $newStatus = $this->dataHelper->getStatusMapRecommend($data['data']['subscription_status']);
                if (!$newStatus) {
                    $this->logger->info('Post data', ['post_data' => $data]);
                } elseif ($subscriberData
                    && $subscriberData['subscriber_status'] != $newStatus
                ) {
                    $this->subscriberResource->getConnection()->update(
                        $this->subscriberResource->getMainTable(),
                        [
                            'subscriber_status' => $newStatus,
                            'change_status_at' => date('Y-m-d H:i:s')
                        ],
                        [
                            'subscriber_id = ?' => $subscriberData['subscriber_id']
                        ]
                    );
                }
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage(), ['exception' => $exception, 'post_data' => $data]);
                $responseData = [
                    'response' => 'rejected'
                ];

            }
        } else {
            $this->logger->info('Post data', ['post_data' => $data]);
        }
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseData);
        return $response;
    }

    private function isValidRequest($data)
    {
        $isValid = is_array($data)
            && isset(
                $data['id'],
                $data['event'],
                $data['event_date'],
                $data['signature'],
                $data['data']['type'],
                $data['data']['subscription_status'],
                $data['data']['identifier']
            )
            && $data['event'] == 'messaging:channel:update'
            && $data['data']['type'] == 'email'
            && !empty($data['data']['identifier']);

        if (!$isValid) {
            return false;
        }

        $newStatus = $this->dataHelper->getStatusMapRecommend($data['data']['subscription_status']);
        if ($newStatus === null) {
            return false;
        }

        $secretKey = $this->dataHelper->getHashSecretKey($this->storeManagerInterface->getStore()->getId());
        if (empty($secretKey)) {
            return false;
        }

        return sha1($data['id'] . $data['event'] . $data['event_date'] . $secretKey) == $data['signature'];
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

}

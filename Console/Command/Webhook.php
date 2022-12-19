<?php

namespace Superb\Recommend\Console\Command;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Webhook extends \Symfony\Component\Console\Command\Command
{
    const STORE_ID = 'store-id';
    const EVENT_NAME = 'event';
    const COMMAND = 'cmd';
    const WEBHOOK_CODE = 'webhook-code';
    const ALLOVED_EVENT_NAMES = [
        'messaging:channel:update'
    ];
    const WEBHOOK_PATH = 'recommend/webhook/';
    const ALLOVED_COMMANDS = [
        'get:all',
        'get:by:code',
        'create',
        'delete'
    ];


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = (int)$input->getOption(self::STORE_ID);
        $eventName = $input->getOption(self::EVENT_NAME);
        $command = $input->getOption(self::COMMAND);
        $webhookCode = $input->getOption(self::WEBHOOK_CODE);

        if (!in_array($command, self::ALLOVED_COMMANDS)) {
            $output->writeln('<error>Entered command not found.</error>');
            return;
        }
        switch ($command) {
            case 'create':
                if (empty($storeId) || !is_int($storeId)) {
                    $output->writeln('<error>Enter a valid store id and make sure it is an integer.</error>');
                    return;
                }

                $dataHelper = ObjectManager::getInstance()->get(\Superb\Recommend\Helper\Data::class);
                $apiHelper = ObjectManager::getInstance()->get(\Superb\Recommend\Helper\Api::class);
                $storeManager = ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class);

                try {
                    $storeManager->getStore($storeId);
                } catch (NoSuchEntityException $e) {
                    $output->writeln('<error>Store with given id was not found! Enter a valid store id.</error>');
                    return;
                }

                if (empty($eventName)) {
                    $output->writeln('<error>Enter event name, for example \"messaging:channel:update\"</error>');
                    return;
                }

                if (!in_array($eventName, self::ALLOVED_EVENT_NAMES, true)) {
                    $output->writeln('<error>Enter event name isn\'t alloved.</error>');
                    return;
                }

                if ($storeId && $eventName) {
                    $secretKey = $dataHelper->getHashSecretKey($storeId);

                    if (is_null($secretKey)) {
                        $output->writeln('<error>Please enter API Hash Key in Store > Configuration > Recommend > General Settings > API Hash Key, for custom store</error>');
                        return;
                    }

                    $isEnabled = $dataHelper->isEnabled($storeId);
                    if (!$isEnabled) {
                        $output->writeln('<error>The Recommend module is disabled for this store!</error>');
                        return;
                    }
                    $baseStoreUrl = $storeManager->getStore($storeId)->getBaseUrl();
                    $webhookUrl = $baseStoreUrl . self::WEBHOOK_PATH;
                    $webhookCode = $storeManager->getStore($storeId)->getCode() . '_' . $eventName;
                    $websiteId = $storeManager->getStore($storeId)->getWebsiteId();
                    $websiteCode = $storeManager->getWebsite($websiteId)->getCode();

                    $resultCreate = $apiHelper->createWebhook($webhookUrl, $webhookCode, $eventName, $secretKey, $websiteCode);
                    if (!$resultCreate) {
                        $output->writeln('<error>Error creating webhook! Check entered parameters and messages in logs.</error>');
                        return;
                    }
                    $preparedOutput = sprintf('Webhook %s successfully created!', $webhookCode);
                    $output->writeln('<info>' . $preparedOutput . '</info>');
                    return;
                }
                break;
            case 'delete':
                $apiHelper = ObjectManager::getInstance()->get(\Superb\Recommend\Helper\Api::class);
                $storeManager = ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class);

                if (empty($storeId) || !is_int($storeId)) {
                    $output->writeln('<error>Enter a valid store id and make sure it is an integer.</error>');
                    return;
                }

                try {
                    $storeManager->getStore($storeId);
                } catch (NoSuchEntityException $e) {
                    $output->writeln('<error>Store with given id was not found! Enter a valid store id.</error>');
                    return;
                }

                $websiteId = $storeManager->getStore($storeId)->getWebsiteId();
                $websiteCode = $storeManager->getWebsite($websiteId)->getCode();
                $result = $apiHelper->deleteWebhook($webhookCode, $websiteCode);
                $preparedOutput = sprintf('Webhook with code %s don\'t delete.', $webhookCode);
                if (!$result) {
                    $output->writeln('<info>' . $preparedOutput . '</info>');
                    return;
                }
                $preparedOutput = sprintf('Webhook with code %s removed successfully!', $webhookCode);
                $output->writeln('<info>' . $preparedOutput . '</info>');
                break;
            case 'get:all':
            case 'get:by:code':
            default:
                $preparedOutput = sprintf('The command %s you entered is not yet supported and is under development', $command);
                $output->writeln('<info>' . $preparedOutput . '</info>');
                break;
        }
    }

    protected function configure()
    {
        $options = [
            new InputOption(
                self::COMMAND,
                null,
                InputOption::VALUE_REQUIRED,
                'Command name, for example "create, delete, get:all, get:by:code"'
            ),
            new InputOption(
                self::STORE_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'Store ID'
            ),
            new InputOption(
                self::EVENT_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Event name, for example "messaging:channel:update"'
            ),
            new InputOption(
                self::WEBHOOK_CODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Webhook code, for example "default_messaging:channel:update"'
            )
        ];
        $this->setName('recommend:webhook');
        $this->setDescription('Create webhook for specific store and event');
        $this->setDefinition($options);
        parent::configure();
    }
}

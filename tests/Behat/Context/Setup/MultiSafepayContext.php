<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace Tests\BitBag\SyliusMultiSafepayPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use BitBag\SyliusMultiSafepayPlugin\ApiClient\MultiSafepayApiClientInterface;
use BitBag\SyliusMultiSafepayPlugin\MultiSafepayGatewayFactory;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class MultiSafepayContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var PaymentMethodRepositoryInterface */
    private $paymentMethodRepository;

    /** @var ExampleFactoryInterface */
    private $paymentMethodExampleFactory;

    /** @var FactoryInterface */
    private $paymentMethodTranslationFactory;

    /** @var EntityManagerInterface */
    private $paymentMethodManager;

    public function __construct(
        SharedStorageInterface $sharedStorage,
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ExampleFactoryInterface $paymentMethodExampleFactory,
        FactoryInterface $paymentMethodTranslationFactory,
        EntityManagerInterface $paymentMethodManager
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentMethodExampleFactory = $paymentMethodExampleFactory;
        $this->paymentMethodTranslationFactory = $paymentMethodTranslationFactory;
        $this->paymentMethodManager = $paymentMethodManager;
    }

    /**
     * @Given the store has a payment method :arg1 with a code :arg2 and MultiSafepay payment gateway
     */
    public function theStoreHasAPaymentMethodWithACodeAndMultisafepayPaymentGateway(
        string $paymentMethodName,
        string $paymentMethodCode
    ): void {
        $paymentMethod = $this->createPaymentMethodMultiSafepay(
            $paymentMethodName,
            $paymentMethodCode,
            MultiSafepayGatewayFactory::FACTORY_NAME,
            'MultiSafepay'
        );

        $paymentMethod->getGatewayConfig()->setConfig([
            'apiKey' => 'test',
            'sandbox' => true,
            'payum.http_client' => '@bitbag_sylius_multisafepay_plugin.api_client.multisafepay_api_client',
            'type' => MultiSafepayApiClientInterface::REDIRECT_ORDER_TYPE,
        ]);

        $this->paymentMethodManager->flush();
    }

    private function createPaymentMethodMultiSafepay(
        string $name,
        string $code,
        string $factoryName,
        string $description = '',
        bool $addForCurrentChannel = true,
        int $position = null
    ): PaymentMethodInterface {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $this->paymentMethodExampleFactory->create([
            'name' => ucfirst($name),
            'code' => $code,
            'description' => $description,
            'gatewayName' => $factoryName,
            'gatewayFactory' => $factoryName,
            'enabled' => true,
            'channels' => ($addForCurrentChannel && $this->sharedStorage->has('channel')) ? [$this->sharedStorage->get('channel')] : [],
        ]);

        if (null !== $position) {
            $paymentMethod->setPosition($position);
        }

        $this->sharedStorage->set('payment_method', $paymentMethod);
        $this->paymentMethodRepository->add($paymentMethod);

        return $paymentMethod;
    }
}

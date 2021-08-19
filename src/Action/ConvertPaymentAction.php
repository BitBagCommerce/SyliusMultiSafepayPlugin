<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);
namespace BitBag\SyliusMultiSafepayPlugin\Action;

use BitBag\SyliusMultiSafepayPlugin\Action\Api\ApiAwareTrait;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Convert;
use Sylius\Bundle\PayumBundle\Provider\PaymentDescriptionProviderInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

final class ConvertPaymentAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait, ApiAwareTrait;

    /** @var PaymentDescriptionProviderInterface */
    private $paymentDescriptionProvider;

    public function __construct(PaymentDescriptionProviderInterface $paymentDescriptionProvider)
    {
        $this->paymentDescriptionProvider = $paymentDescriptionProvider;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        /** @var CustomerInterface $customer */
        $customer = $order->getCustomer();

        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $order->getShippingAddress();

        /** @var AddressInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details['paymentData'] = [
            'type' => $this->multiSafepayApiClient->getType(),
            'order_id' => sprintf('%d-%d-%s', $order->getId(), $payment->getId(), $billingAddress->getCountryCode()),
            'currency' => $order->getCurrencyCode(),
            'amount' => $payment->getAmount(),
            'description' => $this->paymentDescriptionProvider->getPaymentDescription($payment),
            'customer' => [
                'locale' => $order->getLocaleCode(),
                'ip_address' => $order->getCustomerIp(),
                'first_name' => $shippingAddress->getFirstName(),
                'last_name' => $shippingAddress->getLastName(),
                'address1' => $shippingAddress->getStreet(),
                'zip_code' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'country' => $shippingAddress->getCountryCode(),
                'phone' => $shippingAddress->getPhoneNumber(),
                'email' => $customer->getEmail(),
            ],
        ];

        $request->setResult((array) $details);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}

<?php

namespace Adaptis\Payment\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const KEY_MERCHANT_CODE = 'merchant_code';
    public const KEY_MERCHANT_KEY = 'merchant_key';
    public const KEY_SHOW_AVAILABLE_PAYMENT_TYPES = 'show_available_payment_types';
    public const KEY_ONLINE_BANKING_METHODS = 'online_banking_methods';
    public const KEY_CREDIT_CARD_METHODS = 'credit_card_methods';
    public const KEY_WALLET_METHODS = 'wallet_methods';
    public const KEY_BUY_NOW_PAY_LATER_METHODS = 'buy_now_pay_later_methods';
    public const KEY_GROUP_PAYMENT_METHODS_BY_TYPE_ON_CHECKOUT = 'group_payment_methods_by_type_on_checkout';
    public const KEY_SANDBOX = 'sandbox';

    public const PAYMENT_TYPES = [
        'ONLINE_BANKING'    => [
            ['id' => 6, 'name' => 'Maybank2U', 'logo' => '6-maybank2u.png'],
            ['id' => 8, 'name' => 'Alliance Online (Personal)', 'logo' => '8-alliance.png'],
            ['id' => 10, 'name' => 'AmBank', 'logo' => '10-ambank.png'],
            ['id' => 14, 'name' => 'RHB Bank', 'logo' => '14-rhb.png'],
            ['id' => 15, 'name' => 'Hong Leong Bank', 'logo' => '15-hlb.png'],
            ['id' => 20, 'name' => 'CIMB Clicks', 'logo' => '20-cimb.png'],
            ['id' => 31, 'name' => 'Public Bank', 'logo' => '31-public.png'],
            ['id' => 102, 'name' => 'Bank Rakyat', 'logo' => '102-bank-rakyat.png'],
            ['id' => 103, 'name' => 'Affin Bank', 'logo' => '103-affin.png'],
            //            ['id' => 122, 'name' => 'Pay4Me (Delay payment)', 'logo' => 'placeholder.png'],
            ['id' => 124, 'name' => 'BSN', 'logo' => '124-bsn.png'],
            ['id' => 134, 'name' => 'Bank Islam', 'logo' => '134-bank-islam.png'],
            ['id' => 152, 'name' => 'UOB Bank', 'logo' => '152-uob.png'],
            ['id' => 166, 'name' => 'Bank Muamalat', 'logo' => '166-bank-mualamat.png'],
            ['id' => 167, 'name' => 'OCBC Bank', 'logo' => '167-ocbc.png'],
            ['id' => 168, 'name' => 'Standard Chartered Bank', 'logo' => '168-standard-chartered.png'],
            ['id' => 178, 'name' => 'Maybank2E', 'logo' => '178-maybank2e.png'],
            ['id' => 198, 'name' => 'HSBC Bank', 'logo' => '198-hsbc.png'],
            ['id' => 199, 'name' => 'Kuwait Finance House', 'logo' => '199-kuwait-finance-house.png'],
            ['id' => 18, 'name' => 'China UnionPay Online Banking (MYR)', 'logo' => '18-union-pay.png'],
            ['id' => 405, 'name' => 'Agro Bank', 'logo' => '405-agro-bank.png'],
        ],
        'CREDIT_CARD'       => [
            ['id' => 2, 'name' => 'Credit Card (MYR)', 'logo' => '2-credit-card.png'],
            ['id' => 55, 'name' => 'Credit Card (MYR) Pre-Auth', 'logo' => '55-credit-card-pre-auth.png'],
            ['id' => 111, 'name' => 'Public Bank ZIIP (Installment Payment)', 'logo' => '111-public-bank-ziip.png'],
            ['id' => 112, 'name' => 'Maybank EzyPay (Visa/Mastercard Installment Payment)', 'logo' => '112-maybank-ezy-pay-visa-master.png'],
            ['id' => 115, 'name' => 'Maybank EzyPay (AMEX Installment Payment)', 'logo' => '115-maybank-ezy-pay-amex.png'],
            ['id' => 157, 'name' => 'HSBC (Installment Payment)', 'logo' => '157-hsbc-installment-payment.png'],
            ['id' => 174, 'name' => 'CIMB Easy Pay (Installment Payment)', 'logo' => '174-cimb-easy-pay.png'],
            ['id' => 179, 'name' => 'Hong Leong Bank Flexi Payment (Installment Payment)', 'logo' => '179-hlb-flexi-payment.png'],
            ['id' => 235, 'name' => 'Citibank FlexiPayment (Installment Payment)', 'logo' => '235-citibank-flexi-payment.png'],
            ['id' => 534, 'name' => 'RHB (Installment Payment)', 'logo' => '534-rhb-installment-payment.png'],
        ],
        'WALLET'            => [
            //            ['id' => 22, 'name' => 'Kiple Online', 'logo' => 'placeholder.png'],
            ['id' => 48, 'name' => 'PayPal (MYR)', 'logo' => '48-paypal.png'],
            ['id' => 210, 'name' => 'Boost Wallet Online', 'logo' => '210-boost.png'],
            ['id' => 244, 'name' => 'MCash', 'logo' => '244-mcash.png'],
            ['id' => 382, 'name' => 'NETS QR Online', 'logo' => '382-nets-qr.png'],
            ['id' => 391, 'name' => 'Big Loyalty', 'logo' => '391-big-loyalty.png'],
            ['id' => 523, 'name' => 'GrabPay Online', 'logo' => '523-grab-pay.png'],
            ['id' => 538, 'name' => 'Touch \'n Go eWallet', 'logo' => '538-tng-ewallet.png'],
            ['id' => 542, 'name' => 'Maybank PayQR Online', 'logo' => '542-mae.png'],
            ['id' => 801, 'name' => 'ShopeePay Online', 'logo' => '801-shopee-pay.png'],
            ['id' => 912, 'name' => 'Setel', 'logo' => '912-setel.png'],
        ],
        'BUY_NOW_PAY_LATER' => [
            ['id' => 890, 'name' => 'Mobypay', 'logo' => '890-mobypay.jpg'],
            ['id' => 891, 'name' => 'Atome', 'logo' => '891-atome.png'],
        ],
    ];

    public const PAYMENT_STATUS_FAIL = "1400";
    public const PAYMENT_STATUS_SUCCESS = "1300";
    public const PAYMENT_STATUS_PENDING = "1100";

    /**
     * Get merchant code
     *
     * @return string|null
     */
    public function getMerchantCode(): ?string
    {
        return $this->getValue(self::KEY_MERCHANT_CODE);
    }

    /**
     * Get merchant key
     *
     * @return string|null
     */
    public function getMerchantKey(): ?string
    {
        return $this->getValue(self::KEY_MERCHANT_KEY);
    }

    /**
     * @return bool
     */
    public function getShowAvailablePaymentTypes(): bool
    {
        return (bool) $this->getValue(self::KEY_SHOW_AVAILABLE_PAYMENT_TYPES);
    }

    /**
     * Get online banking methods
     *
     * @return array
     */
    public function getOnlineBankingMethods(): array
    {
        $enabledMethodIds = explode(',', $this->getValue(self::KEY_ONLINE_BANKING_METHODS) ?: '');

        return array_values(array_Filter(self::PAYMENT_TYPES['ONLINE_BANKING'], function ($type) use ($enabledMethodIds) {
            return in_array($type['id'], $enabledMethodIds);
        }));
    }

    /**
     * Get credit card methods
     *
     * @return array
     */
    public function getCreditCardMethods(): array
    {
        $enabledMethodIds = explode(',', $this->getValue(self::KEY_CREDIT_CARD_METHODS) ?: '');

        return array_values(array_Filter(self::PAYMENT_TYPES['CREDIT_CARD'], function ($type) use ($enabledMethodIds) {
            return in_array($type['id'], $enabledMethodIds);
        }));
    }

    /**
     * Get wallet methods
     *
     * @return array
     */
    public function getWalletMethods(): array
    {
        $enabledMethodIds = explode(',', $this->getValue(self::KEY_WALLET_METHODS) ?: '');

        return array_values(array_Filter(self::PAYMENT_TYPES['WALLET'], function ($type) use ($enabledMethodIds) {
            return in_array($type['id'], $enabledMethodIds);
        }));
    }

    /**
     * Get online banking methods
     *
     * @return array
     */
    public function getBuyNowPayLaterMethods(): array
    {
        $enabledMethodIds = explode(',', $this->getValue(self::KEY_BUY_NOW_PAY_LATER_METHODS) ?: '');

        return array_values(array_Filter(self::PAYMENT_TYPES['BUY_NOW_PAY_LATER'], function ($type) use ($enabledMethodIds) {
            return in_array($type['id'], $enabledMethodIds);
        }));
    }


    /**
     * Get group payment methods by type on checkout
     *
     * @return boolean
     */
    public function getGroupPaymentMethodsByTypeOnCheckout(): bool
    {
        return (bool) $this->getValue(self::KEY_GROUP_PAYMENT_METHODS_BY_TYPE_ON_CHECKOUT);
    }

    /**
     * Get sandbox
     *
     * @return string|null
     */
    public function getSandbox(): ?bool
    {
        return $this->getValue(self::KEY_SANDBOX);
    }
}


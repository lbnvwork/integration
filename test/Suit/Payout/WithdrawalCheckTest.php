<?php

namespace Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use Exception;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck\CheckStatusEvent as WithdrawalCheckStatusEvent;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\WithdrawalCheck\CoreHandler as WithdrawalCheckHandler;
use Plus\PaymentSystem\Response\ArrayResponse;
use Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\AbstractTest;

class WithdrawalCheckTest extends AbstractTest
{
    private const WITHDRAWAL_ID = '111';
    private const LOCAL_PATH = '/Payout/withdrawalCheck/';

    /**
     * @dataProvider dataProvider
     *
     * @return void
     *
     * @throws Exception
     */
    public function test(array $psToPlusResponses, array $plusResponse)
    {
        $this->registerSequenceGenerator();

        $this->createHttpClientMock($psToPlusResponses);

        $gateSettings = $this->createGSMock();
        $gateRequest = $this->prepareGatePlusRequest($this->getFixture('Payout/core_to_plus.json'));
        (new WithdrawalCheckStatusEvent(
            $gateRequest,
            $gateSettings,
            self::WITHDRAWAL_ID
        )
        )->checkStatusProcess();

        $this->assertCount(1, $this->gateCallbacks);
        $this->assertEquals($plusResponse, (new ArrayResponse($this->gateCallbacks[0]))->prepare());
    }

    /**
     * @return array[]
     */
    public function dataProvider(): array
    {
        return [
            WithdrawalCheckHandler::FAILED                     => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'failed/ps_response.json',
                            200
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'failed/plus_response.json')
            ],
            WithdrawalCheckHandler::ERROR_400                  => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_400/ps_response.json',
                            400
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_400/plus_response.json')
            ],
            WithdrawalCheckHandler::ERROR_401_404              => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_401/ps_response.json',
                            401
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_401/plus_response.json')
            ],
            WithdrawalCheckHandler::SUCCEEDED                  => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'succeeded/ps_response.json',
                            200
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'succeeded/plus_response.json')
            ],
            WithdrawalCheckHandler::INVALID_AMOUNT_OR_CURRENCY => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'invalid_amount_or_currency/ps_response.json',
                            200
                        )
                    ],
                ],
                $this->prepareArrayFromJson(
                    self::LOCAL_PATH . 'invalid_amount_or_currency/plus_response.json'
                )
            ],
        ];
    }
}

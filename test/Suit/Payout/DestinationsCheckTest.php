<?php

namespace Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\Payout;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Common\AbstractCoreHandler;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck\CheckStatusEvent as DestinationsCheckStatusEvent;
use Plus\PaymentSystem\Processing\SepaViaGeDetails\Payout\DestinationsCheck\CoreHandler as DestinationsCheckHandler;
use Plus\PaymentSystem\Response\ArrayResponse;
use Plus\Test\Suit\PaymentSystem\Processing\SepaViaGeDetails\AbstractTest;

class DestinationsCheckTest extends AbstractTest
{
    public const LOCAL_PATH = '/Payout/destinationsCheck/';

    private const DESTINATIONS_ID = '111';

    /**
     * @dataProvider dataProvider
     *
     * @return void
     *
     * @throws \Exception
     */
    public function test(array $psToPlusResponses, array $plusResponse, array $checkStatusEvent = null)
    {
        $this->registerSequenceGenerator();
        $this->createHttpClientMock($psToPlusResponses);

        if ($checkStatusEvent) {
            $this->registerEventQueueMock(...array_values($checkStatusEvent));
        }
        $gateSettings = $this->createGSMock();
        $gateRequest = $this->prepareGatePlusRequest($this->getFixture('Payout/core_to_plus.json'));
        (new DestinationsCheckStatusEvent(
            $gateRequest,
            $gateSettings,
            self::DESTINATIONS_ID
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
            DestinationsCheckHandler::AUTHORIZED      => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'authorized/ps_response.json',
                            200
                        )
                    ],
                    $this->getWithdrawalSuccessResponse()
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'success_plus_response.json')
            ],
            DestinationsCheckHandler::ERROR_400       => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'decline_0_400/ps_response.json',
                            400
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'decline_0_400/plus_response.json')
            ],
            DestinationsCheckHandler::ERROR_401_404   => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'error_401_404/ps_response.json',
                            401
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'error_401_404/plus_response.json')
            ],
            AbstractCoreHandler::NETWORK_ERROR => [
                [
                    [
                        'exception' => new RequestException(
                            self::LOCAL_PATH . 'network error',
                            new GuzzleRequest('POST', '')
                        ),
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'network_error/plus_response.json'),
            ],
            AbstractCoreHandler::MALFORMED     => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'malformed/ps_response.json',
                            200
                        )
                    ],
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'malformed/plus_response.json'),
            ],
            'InvalidHttpResponseCode'                 => [
                [
                    [
                        'ps_response' => $this->preparePsResponse(
                            self::LOCAL_PATH . 'invalidHttpResponseCode/ps_response.json',
                            405
                        ),
                    ]
                ],
                $this->prepareArrayFromJson(self::LOCAL_PATH . 'invalidHttpResponseCode/plus_response.json'),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getWithdrawalSuccessResponse(): array
    {
        return [
            'ps_response' => $this->preparePsResponse(
                self::LOCAL_PATH . 'ps_response_withdrawal.json',
                202
            )
        ];
    }
}

<?php

namespace NW\WebService\References\Operations\Notification;

class TsReturnOperation extends ReferencesOperation
{
    public const TYPE_NEW    = 1;
    public const TYPE_CHANGE = 2;

    protected array $errorMessage = [];
    protected Seller $reseller;
    protected Employee $creator;
    protected Employee $expert;
    protected Contractor $client;

    protected int $resellerId;
    protected int $clientId;
    protected int $creatorId;
    protected int $expertId;
    protected int $notificationType;
    protected int $complaintId;
    protected int $consumptionId;
    protected string $complaintNumber;
    protected string $consumptionNumber;
    protected string $agreementNumber;
    protected string $date;
    protected array $differences = [];

    /**
     * Я совместил тут фильтрацию и валидацию ввода
     * Но, по хорошему, фильтрация должна быть там, откуда вызывается doOperation, что бы данные передавались сюда
     * уже фильтрованым массивом.
     * А с валидацией, обычно, помогает фреймворк...
     * @throws \Exception
     */
    protected function load()
    {
        $data = (array)$this->getRequest('data');

        foreach (['resellerId', 'clientId', 'creatorId', 'expertId', 'notificationType', 'complaintId', 'consumptionId'] as $field) {
            $this->$field = (int) $data[$field];
            if(empty($this->$field)){
                $this->errorMessage[] = 'Empty '.$field;
            }
        }

        if (!in_array($this->notificationType, [self::TYPE_NEW, self::TYPE_CHANGE])) {
            $this->errorMessage[] = 'Incorrect notificationType value. Available values are: '.self::TYPE_NEW.' and '.self::TYPE_CHANGE;
        }

        foreach (['complaintNumber', 'consumptionNumber', 'agreementNumber', 'date'] as $field) {
            $this->$field = (string) $data[$field];
            if(empty($this->$field)){
                $this->errorMessage[] = 'Empty '.$field;
            }
        }

        if (!empty($data['differences'])) {
            $this->differences = [
                'from' => $data['differences']['from'] ?? null,
                'to' => $data['differences']['to'] ?? null,
            ];
        }

        if (
            $this->notificationType === self::TYPE_CHANGE &&
            (empty($this->differences) || empty($this->differences['from'])  || empty($this->differences['to']))
        ) {
            $this->errorMessage[] = 'Old (differences[from]) and new (differences[to]) statuses are mandatory for notificationType: '.$this->notificationType;
        }

        if (!empty($this->errorMessage)) {
            throw new \Exception(implode(PHP_EOL, $this->errorMessage), 417);
        }

        $this->reseller = self::getEntityById('Seller', $this->resellerId);
        $this->creator = self::getEntityById('Employee', $this->creatorId);
        $this->expert = self::getEntityById('Employee', $this->expertId);
        $this->client = self::getEntityById('Contractor', $this->clientId);

        if ($this->client->type !== Contractor::TYPE_CUSTOMER || $this->client->Seller->id !== $this->resellerId) {
            throw new \Exception('сlient not found!', 404);
        }
    }

    /**
     * @throws \Exception
     */
    public function doOperation(): array
    {
        $result = [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail'   => false,
            'notificationClientBySms'     => [
                'isSent'  => false,
                'message' => '',
            ],
        ];

        if (empty($this->resellerId)) {
            $result['notificationClientBySms']['message'] = 'Empty resellerId';
            return $result;
        }

        $templateData = $this->prepareTemplateData();

        $emailFrom = FakeSettingsHelper::getResellerEmailFrom();
        if (!empty($emailFrom)) {
            // Получаем email сотрудников из настроек
            $emails = FakeSettingsHelper::getEmailsByPermit($this->reseller, 'tsGoodsReturn');
            foreach ($emails as $email) {
                $result['notificationEmployeeByEmail'] = MessagesClient::sendMessage(
                    [
                        'emailFrom' => $emailFrom,
                        'emailTo' => $email,
                        'subject' => TemplateHelper::__(
                            'complaintEmployeeEmailSubject',
                            $templateData,
                            $this->reseller
                        ),
                        'message' => TemplateHelper::__(
                            'complaintEmployeeEmailBody',
                            $templateData,
                            $this->reseller
                        ),
                    ]
                );
            }

            // Шлём клиентское уведомление, только если произошла смена статуса
            if ($this->notificationType === self::TYPE_CHANGE) {
                if (!empty($this->client->email)) {
                    $templateData['CLIENT_EMAIL']  = $this->client->email;
                    $result['notificationClientByEmail'] = MessagesClient::sendMessage(
                        [
                            'emailFrom' => $emailFrom,
                            'emailTo' => $this->client->email,
                            'subject' => TemplateHelper::__(
                                'complaintClientEmailSubject',
                                $templateData,
                                $this->reseller
                            ),
                            'message' => TemplateHelper::__(
                                'complaintClientEmailBody',
                                $templateData,
                                $this->reseller
                            ),        
                        ]
                    );
                }

                if (!empty($this->client->phone)) {
                    $res = NotificationManager::send(
                        $this->client->phone,
                        $templateData,
                        $this->errorMessage
                    );
                    if ($res) {
                        $result['notificationClientBySms']['isSent'] = true;
                    }
                    if (!empty($this->errorMessage)) {
                        $result['notificationClientBySms']['message'] = implode(PHP_EOL, $this->errorMessage);
                    }
                }
            }
        }

        return $result;
    }

    protected function prepareTemplateData(): array
    {
       return [
            'COMPLAINT_ID'       => $this->complaintId,
            'COMPLAINT_NUMBER'   => $this->complaintNumber,
            'CREATOR_ID'         => $this->creatorId,
            'CREATOR_NAME'       => $this->creator->getFullName(),
            'EXPERT_ID'          => $this->expertId,
            'EXPERT_NAME'        => $this->expert->getFullName(),
            'CLIENT_ID'          => $this->clientId,
            'CLIENT_NAME'        => $this->client->getFullName(),
            'CONSUMPTION_ID'     => $this->consumptionId,
            'CONSUMPTION_NUMBER' => $this->consumptionNumber,
            'AGREEMENT_NUMBER'   => $this->agreementNumber,
            'DATE'  			 => $this->date,
           //как я люблю PHP 8. Match - это то, о чём я мечтал всякий раз, имея дело со вложеными тернарными операторами...
            'DIFFERENCES'        => match($this->notificationType) {
                self::TYPE_NEW      => TemplateHelper::__('NewPositionAdded', ['RETURN_STATUS' => NotificationEvents::NEW_RETURN_STATUS]),
                self::TYPE_CHANGE   => TemplateHelper::__('PositionStatusHasChanged', [
                    'FROM' => Status::getName($this->differences['from']),
                    'TO'   => Status::getName($this->differences['to']),
                    'RETURN_STATUS' => NotificationEvents::CHANGE_RETURN_STATUS
                ]),
            },
            'RETURN_STATUS'      => match($this->notificationType) {
                self::TYPE_NEW => NotificationEvents::NEW_RETURN_STATUS,
                self::TYPE_CHANGE => NotificationEvents::CHANGE_RETURN_STATUS,
            },
        ];
    }
}

<?php

namespace Citrus\MarketplaceApi;

/**
 * @property string coupon Купон
 * @property bool isProlongation Продление
 * @property string name Имя покупателя
 * @property string email Email покупателя
 * @property int $partnerId ID партнера
 * @property int orderId ID заказа
 * @property int prolongationPeriod Срок продления
 * @property string prolongationPeriodType Единицы измерения срока продления
 * -property string dateCreate Дата создания купона (не передается)
 * @property float price Стоимость
 * @property string currency Валюта
 * @property string clientKeyHash Хэш ключа клиента
 * @property string seller Тип
 */
class CallbackActivateCoupon extends Callback
{

}
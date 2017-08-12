<?php
namespace leolei\AllinPay;

use leolei\AllinPay\Config;

/**
 * ͨ��H5֧����
 * Class WapPay
 */
class WapPay
{
    //�����ַ
    private $wapPayUrl;
    private $userSignUrl;
    //������Ϣ
    private $version  = 'v1.0';
    private $signType = '0';
    private $payType = '33';
    //�̻���Ϣ
    private $mer_id;
    private $front_url;
    private $back_url;
    //������Ϣ
    private $order_id;
    private $txn_amt;
    private $txn_time;
    private $user_id;
    //common
    private $key;

    /**
     * ��ʼ����������
     *
     * @author leolei <346991581@qq.com>
     */
    public function __construct()
    {
        //ͨѶ��ַ
        $this->wapPayUrl = Config::wapPayUrl();
        $this->userSignUrl = Config::userSignUrl();
        //��������
        $this->mer_id = Config::getMerId(); //�̻���
        $this->key = Config::getSign(); //֤������
    }

    /**
     * H5���֧���ӿ�
     *
     * @return void
     * @author leolei <346991581@qq.com>
     */
    public function consume()
    {
        //���ò���
        $params = [
            'inputCharset'  => '1', //���뷽ʽ 1:utf-8 2:gbk 3:gb2312
            'pickupUrl'     => $this->front_url, //ǰ̨֪ͨ��ַ
            'receiveUrl'    => $this->back_url, //��̨֪ͨ��ַ
            'version'       => $this->version, //�汾��
            'language'      => '1', //�������� 1-�������� 2-�������� 3-Ӣ��
            'signType'      => '0', // 0-md5 1-֤��
            'merchantId'    => $this->mer_id,
            'payerName'     => '',
            'payerEmail'    => '',
            'payerTelephone'=> '',
            'payerIDCard'   => '',
            'pid'           => '',
            'orderNo'       => $this->order_id,//������
            'orderAmount'   => $this->txn_amt,//�������
            'orderCurrenc'  => '0',
            'orderDatetime' => $thi->txn_time,//���׷���ʱ��
            'orderExpireDatetime'=> '',
            'productName'   => '',
            'productPrice'  => '',
            'productNum'    => '',
            'productId'     => '',
            'productDesc'   => '',
            'ext1'          => '<USER>'.$this->user_id.'</USER>',
            'ext2'          => '',
            'extTL'         => '',
            'payType'       => $this->payType,
            'issuerId'      => '',
            'pan'           => '',
            'tradeNature'   => '' //ѡ��
        ];

        //����ǩ��
        $params['signMsg'] = $this->makeSignature($params);
        //�׳���---ǰ̨�ص�
        $html_form = self::createAutoFormHtml($params, $this->wapPayUrl);

        return $html_form;
    }

    /**
     * ͨ������ע��
     *
     * @return void
     * @author leolei <346991581@qq.com>
     */
    public function user_sign()
    {
        //���ò���
        $params = [
            'signType'      => '0', // 0-md5 1-֤��
            'merchantId'    => $this->mer_id,
            'partnerUserId' => $this->user_id,
        ];

        //����ǩ��
        $params['signMsg'] = $this->makeSignature($params);

        $data = http_build_query($params);
        
        $opts = [
            'http' => [
                'method'=>"POST",
                'header'=>"Content-type: application/x-www-form-urlencoded\r\n"."Content-length:".strlen($data)."\r\n" ."Cookie: foo=bar\r\n" ."\r\n",
                'content' => $data
            ]
        ];

        $cxContext = stream_context_create($opts);
        $sFile = file_get_contents($this->userSignUrl, false, $cxContext);
        $extra = json_decode($sFile, true);

        if ($extra) {
            return $extra['userId'];
        } else {
            return fasle;
        }
    }

    /**
     * �����ַ�����֤ǩ��
     *
     * @param array $data
     * @return void
     * @author leolei <346991581@qq.com>
     */
    public function verify($data = null)
    {
        // ���ж��Ƿ��з��ز���
        if (!$data) {
            if (empty($_POST) && empty($_GET)) {
                return false;
            }
            $data = $_POST ?  : $_GET;
        }

        $sign = $data ['signMsg'];
        unset($data['signMsg']);
        $res_sign = $this->makeSignature($data);
        if ($sign == $res_sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ֧���ַ���ǩ��
     *
     * @param array $params
     * @return void
     * @author leolei <346991581@qq.com>
     */
    private function makeSignature($params)
    {
        // ���������
        foreach ($params as $key => $val) {
            if ($val == '') {
                unset($params[$key]);
            }
        }
        $query = http_build_query($params);
        $query .= $query."&key=".$this->key;
        return strtoupper(md5($query));
    }

    /**
     * ����H5֧����
     * @param $params
     * @param $reqUrl
     * @return string
     */
    public static function createAutoFormHtml($params, $reqUrl)
    {
        $encodeType = isset ( $params ['encoding'] ) ? $params ['encoding'] : 'UTF-8';
        $html = <<<eot
        <html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
        </head>
        <body onload="javascript:document.pay_form.submit();">
        <form id="pay_form" name="pay_form" action="{$reqUrl}" method="post">
eot;
        foreach ($params as $key => $value) {
            $html .= "<input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
        </form>
        </body>
        </html>
eot;
        return $html;
    }
}

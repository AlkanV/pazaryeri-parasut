<?php

namespace salyangoz\pazaryeriparasut\Marketplace;

use salyangoz\pazaryeriparasut;

class Gittigidiyor extends Marketplace
{

    private $gittigidiyor;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $gittigidiyorConfig =   [
            'apiKey'            =>  array_get($config,'gittigidiyor_api_key'),
            'secretKey'         =>  array_get($config,'gittigidiyor_secret_key'),
            'nick'              =>  array_get($config,'gittigidiyor_username'),
            'password'          =>  array_get($config,'gittigidiyor_password'),
            'auth_user'         =>  array_get($config,'gittigidiyor_auth_user'),
            'auth_pass'         =>  array_get($config,'gittigidiyor_auth_password'),
            'lang'              =>  array_get($config,'gittigidiyor_lang'),
            'developer_base_url'=>  array_get($config,'gittigidiyor_developer_base_url'),
            'product_base_url'  =>  array_get($config,'gittigidiyor_product_base_url')
        ];

        $this->gittigidiyor =   new pazaryeriparasut\Library\Gittigidiyor($gittigidiyorConfig);
    }

    /**
     * Bir satışı işler
     * @param $sale
     */
    private function processSale($sale)
    {

        /** Sipari tutarı 0 tl ise atlıyor */
        if($sale->price == 0)
            return;

        $parasutAdapter =   new pazaryeriparasut\ParasutAdapter($this->config,"GG");

        $contactType    =   "person";

        if(isset($sale->invoiceInfo))
        {
            $address    =   $sale->invoiceInfo->address;
            if(isset($sale->invoiceInfo->taxNumber))
                $tax        =   $sale->invoiceInfo->taxNumber ? $sale->invoiceInfo->taxNumber :  $sale->invoiceInfo->tcCertificate;
            else
            {
                $tax        =   $sale->invoiceInfo->tcCertificate;
            }
            $district   =   $sale->invoiceInfo->district;
            $phone      =   $sale->invoiceInfo->phoneNumber;
            if(isset($sale->invoiceInfo->taxOffice))
            {
                $taxOffice  =   $sale->invoiceInfo->taxOffice ? $sale->invoiceInfo->taxOffice : $sale->buyerInfo->district;
            }
            else
            {
                $taxOffice  =    $sale->buyerInfo->district;
            }
            if(isset($sale->invoiceInfo->taxOffice))
            {
                if($sale->invoiceInfo->taxOffice)
                    $contactType    =   "company";
            }

            $fullname   =   $sale->invoiceInfo->companyTitle ? $sale->invoiceInfo->companyTitle : $sale->invoiceInfo->fullname;
        }
        else
        {
            $address    =   $sale->buyerInfo->address;
            $tax        =   11111111111;
            $district   =   $sale->buyerInfo->district;
            $phone      =   $sale->buyerInfo->phone;
            $taxOffice  =   "";
            $fullname   =   $sale->buyerInfo->name." ".$sale->buyerInfo->surname;
        }

        $parasutAdapter->setContact($contactType,$sale->buyerInfo->username,
            $fullname,
            $address,
            $tax,
            $taxOffice,
            $sale->buyerInfo->city,
            $district,
            $phone,
            ""
            );


        $parasutAdapter->addProduct($sale->productTitle,$sale->productId,$sale->amount,$sale->price);

        $parasutAdapter->saveInvoice($sale->saleCode,$sale->price,$sale->productTitle, date('Y-m-d'));
    }

    private function process($page=1)
    {

        $sales = $this->gittigidiyor->getPagedSales(true, 'S', '', 'A', 'D', $page);
		
		if(!is_array($sales->sales->sale))
		{
			$saleList = $sales->sales;
		}
		else
		{
			$saleList = array_reverse($sales->sales->sale);
		}

        foreach ($saleList as $sale)
        {
            $this->processSale($sale);
        }

        if($sales->nextPageAvailable)
            return $this->process($page++);
    }

    public function transfer()
    {
        $this->process();
    }
}
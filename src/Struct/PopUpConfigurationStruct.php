<?php declare(strict_types=1);

namespace Klaviyo\Integration\Struct;

use Shopware\Core\Framework\Struct\Struct;


class PopUpConfigurationStruct extends Struct
{
    /**
     * @var String
     */
    protected $popUpOpenBtnColor;

    /**
     * @var String
     */
    protected $popUpOpenBtnBgColor;

    /**
     * @var String
     */
    protected $popUpCloseBtnColor;

    /**
     * @var String
     */
    protected $popUpCloseBtnBgColor;

    /**
     * @var String
     */
    protected $subscribeBtnColor;

    /**
     * @var String
     */
    protected $subscribeBtnBgColor;

    /**
     * @var String
     */
    protected $popUpAdditionalClasses;

    /**
     * @return String
     */
    public function getPopUpOpenBtnColor(): string
    {
        return $this->popUpOpenBtnColor;
    }

    /**
     * @return String
     */
    public function getPopUpOpenBtnBgColor(): string
    {
        return $this->popUpOpenBtnBgColor;
    }

    /**
     * @return String
     */
    public function getPopUpCloseBtnColor(): string
    {
        return $this->popUpCloseBtnColor;
    }

    /**
     * @return String
     */
    public function getPopUpCloseBtnBgColor(): string
    {
        return $this->popUpCloseBtnBgColor;
    }

    /**
     * @return String
     */
    public function getSubscribeBtnColor(): string
    {
        return $this->subscribeBtnColor;
    }

    /**
     * @return String
     */
    public function getSubscribeBtnBgColor(): string
    {
        return $this->subscribeBtnBgColor;
    }

    /**
     * @return String
     */
    public function getPopUpAdditionalClasses(): string
    {
        return $this->popUpAdditionalClasses;
    }

    /**
     * @param String $popUpOpenBtnColor
     */
    public function setPopUpOpenBtnColor(string $popUpOpenBtnColor): void
    {
        $this->popUpOpenBtnColor = $popUpOpenBtnColor;
    }

    /**
     * @param String $popUpOpenBtnBgColor
     */
    public function setPopUpOpenBtnBgColor(string $popUpOpenBtnBgColor): void
    {
        $this->popUpOpenBtnBgColor = $popUpOpenBtnBgColor;
    }

    /**
     * @param String $popUpCloseBtnColor
     */
    public function setPopUpCloseBtnColor(string $popUpCloseBtnColor): void
    {
        $this->popUpCloseBtnColor = $popUpCloseBtnColor;
    }

    /**
     * @param String $popUpCloseBtnBgColor
     */
    public function setPopUpCloseBtnBgColor(string $popUpCloseBtnBgColor): void
    {
        $this->popUpCloseBtnBgColor = $popUpCloseBtnBgColor;
    }

    /**
     * @param String $subscribeBtnColor
     */
    public function setSubscribeBtnColor(string $subscribeBtnColor): void
    {
        $this->subscribeBtnColor = $subscribeBtnColor;
    }

    /**
     * @param String $subscribeBtnBgColor
     */
    public function setSubscribeBtnBgColor(string $subscribeBtnBgColor): void
    {
        $this->subscribeBtnBgColor = $subscribeBtnBgColor;
    }

    /**
     * @param String $popUpAdditionalClasses
     */
    public function setPopUpAdditionalClasses(string $popUpAdditionalClasses): void
    {
        $this->popUpAdditionalClasses = $popUpAdditionalClasses;
    }
}

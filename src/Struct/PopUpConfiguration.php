<?php declare(strict_types=1);

namespace Klaviyo\Integration\Struct;

class PopUpConfiguration
{
    /**
     * @var String
     */
    private String $popUpOpenBtnColor;

    /**
     * @var String
     */
    private String $popUpOpenBtnBgColor;

    /**
     * @var String
     */
    private String $popUpCloseBtnColor;

    /**
     * @var String
     */
    private String $popUpCloseBtnBgColor;

    /**
     * @var String
     */
    private String $subscribeBtnColor;

    /**
     * @var String
     */
    private String $subscribeBtnBgColor;

    /**
     * @var String
     */
    private String $popUpAdditionalClasses;

    public function __construct
    (
        String $popUpOpenBtnColor,
        String $popUpOpenBtnBgColor,
        String $popUpCloseBtnColor,
        String $popUpCloseBtnBgColor,
        String $subscribeBtnColor,
        String $subscribeBtnBgColor,
        String $popUpAdditionalClasses
    )
    {
        $this->popUpOpenBtnColor = $popUpOpenBtnColor;
        $this->popUpOpenBtnBgColor = $popUpOpenBtnBgColor;
        $this->popUpCloseBtnColor = $popUpCloseBtnColor;
        $this->popUpCloseBtnBgColor = $popUpCloseBtnBgColor;
        $this->subscribeBtnColor = $subscribeBtnColor;
        $this->subscribeBtnBgColor = $subscribeBtnBgColor;
        $this->popUpAdditionalClasses = $popUpAdditionalClasses;
    }

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
}

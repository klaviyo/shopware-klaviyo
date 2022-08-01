<?php declare(strict_types=1);

namespace Klaviyo\Integration\Struct;

class PopUpConfiguration
{
    private string $popUpOpenBtnColor;
    private string $popUpOpenBtnBgColor;
    private string $popUpCloseBtnColor;
    private string $popUpCloseBtnBgColor;
    private string $subscribeBtnColor;
    private string $subscribeBtnBgColor;
    private string $popUpAdditionalClasses;

    public function __construct(
        string $popUpOpenBtnColor,
        string $popUpOpenBtnBgColor,
        string $popUpCloseBtnColor,
        string $popUpCloseBtnBgColor,
        string $subscribeBtnColor,
        string $subscribeBtnBgColor,
        string $popUpAdditionalClasses
    ) {
        $this->popUpOpenBtnColor = $popUpOpenBtnColor;
        $this->popUpOpenBtnBgColor = $popUpOpenBtnBgColor;
        $this->popUpCloseBtnColor = $popUpCloseBtnColor;
        $this->popUpCloseBtnBgColor = $popUpCloseBtnBgColor;
        $this->subscribeBtnColor = $subscribeBtnColor;
        $this->subscribeBtnBgColor = $subscribeBtnBgColor;
        $this->popUpAdditionalClasses = $popUpAdditionalClasses;
    }

    public function getPopUpOpenBtnColor(): string
    {
        return $this->popUpOpenBtnColor;
    }

    public function getPopUpOpenBtnBgColor(): string
    {
        return $this->popUpOpenBtnBgColor;
    }

    public function getPopUpCloseBtnColor(): string
    {
        return $this->popUpCloseBtnColor;
    }

    public function getPopUpCloseBtnBgColor(): string
    {
        return $this->popUpCloseBtnBgColor;
    }

    public function getSubscribeBtnColor(): string
    {
        return $this->subscribeBtnColor;
    }

    public function getSubscribeBtnBgColor(): string
    {
        return $this->subscribeBtnBgColor;
    }

    public function getPopUpAdditionalClasses(): string
    {
        return $this->popUpAdditionalClasses;
    }
}

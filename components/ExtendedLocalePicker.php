<?php namespace StudioBosco\TranslateExtended\Components;

use Cms\Classes\ComponentBase;
use Winter\Translate\Components\LocalePicker;
use Winter\Translate\Models\Locale as LocaleModel;
use Winter\Translate\Classes\Translator;

class ExtendedLocalePicker extends LocalePicker
{
    public function componentDetails(): array
    {
        return [
            'name'        => 'Extended Locale Picker',
            'description' => 'studiobosco.translateextended::lang.strings.localepicker_desc'
        ];
    }

    public function defineProperties(): array
    {
        return [];
    }

    public function init()
    {
        $this->translator = Translator::instance();
    }

    public function onRun()
    {
        $this->page['activeLocale'] = $this->activeLocale = $this->translator->getLocale();
        $this->page['locales'] = $this->locales = LocaleModel::listEnabled();
        $this->page['localeLinks'] = $this->makeLinks($this->locales);
    }

    public function makeLinks($locales)
    {
        $links = [];
        foreach ($locales as $key => $locale) {
            $links[$key] = $this->makeLocaleUrlFromPage($key);
        }

        return $links;
    }
}
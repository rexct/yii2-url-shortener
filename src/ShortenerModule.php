<?php
/**
 *
 * Developed by Waizabú <code@waizabu.com>
 *
 *
 */

namespace eseperio\shortener;


use eseperio\shortener\models\Shortener;
use yii\base\Module;
use yii\db\Expression;
use yii\helpers\Url;

/**
 * Class ShortenerModule
 * @package eseperio\shortener
 */
class ShortenerModule extends Module
{

    /**
     *
     * Must have a lenght of 61
     * Default is ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789 in random order
     * @var string
     */
    public $options = "NkAXITYvw3iObfKEaoeCUxqm2Dnrugl9PthVSM8yc7GQdBJHW6Lz4ZsjR15pF";


    /**
     * @var array
     */
    public $urlConfig = [
        '<id:[\d\w]{12}>' => 'shortener/default/parse',
    ];

    /**
     * @param $id
     * @return mixed|string the url
     */
    public function expand($id)
    {
        $model = Shortener::find()
            ->where(['shortened' => (new Expression("BINARY('$id')"))])
            ->one();

        if (!empty($model)) {
//Delete record if is not valid anymore.
            if (!is_null($model->valid_until) && time() > $model->valid_until) {
                $model->delete();
            } else {
                return $model->url;
            }
        }

        return false;

    }

    /**
     * @param $url      string|array It accepts any url format allowed by Yii2
     * @param $lifetime integer Time in seconds that the links must be available
     */
    public function short($url, $lifetime = null)
    {
        $model = new Shortener();
        $model->setAttributes([
            'url' => Url::to($url),
            'valid_until' => empty($lifetime) ? null : time() + $lifetime
        ]);

        if ($model->save())
            return $model->shortened;

        return false;
    }


    /**
     * @return string
     */
    public function getShortId()
    {
        return $this->generateShortId();
    }

    /**
     * Method to generate short id of url
     */
    private function generateShortId()
    {
        $date = new \DateTime();
        $monthYear = $date->format('y') + $date->format('n');
        $dayHour = $date->format('j') + $date->format('G');
        $id = [
            $this->options[$monthYear],
            $this->options[$dayHour],
            $this->options[array_sum(mb_str_split($date->format('u')))],
            $this->options[$date->format('s')],
            substr(str_replace('%', '', urlencode(base64_encode(random_bytes(8)))),0, 8),
        ];
        return join("", $id);
    }
}

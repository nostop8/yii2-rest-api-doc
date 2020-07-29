<?php

namespace nostop8\yii2\rest_api_doc\controllers;

use yii\helpers\BaseInflector;
use yii\helpers\Inflector;
use yii\web\Response;

class DefaultController extends \yii\base\Controller
{

    public function init()
    {
        $view = $this->getView();
        \nostop8\yii2\rest_api_doc\ModuleAsset::register($view);
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        \Yii::$app->response->format = Response::FORMAT_HTML;
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $rules = [];
        foreach (\Yii::$app->urlManager->rules as $urlRule) {
            if ($urlRule instanceof \yii\rest\UrlRule) {
                foreach ($urlRule->controller as $urlName => $controllerName) {
                    $entity = [];
                    $controllerName = strrchr($controllerName, '/') === false ? $controllerName : substr(strrchr($controllerName, '/'), 1);
                    if (strpos($urlName, '/') !== FALSE) {
                        $entity['title'] = Inflector::titleize(str_replace('/', ' - ', $urlName), TRUE);
                    } else {
                        $entity['title'] = str_replace(['/'], '_', ucfirst($controllerName));
                    }
                    $urlRuleReflection = new \ReflectionClass($urlRule);
                    $rulesObject = $urlRuleReflection->getProperty('rules');
                    $rulesObject->setAccessible(true);
                    $generatedRules = $rulesObject->getValue($urlRule);

                    $entity['rules'] = $this->_processRules($generatedRules[$urlName]);

                    $rules[] = $entity;
                }
            }
        }
        return $this->render('index', [
            'rules' => $rules,
        ]);
    }

    function _processRules($generatedRules)
    {
        $rules = [];

        foreach ($generatedRules as $generatedRule) {
            $reflectionObject = new \ReflectionClass($generatedRule);
            $templateObject = $reflectionObject->getProperty('_template');
            $templateObject->setAccessible(true);
            if (empty($generatedRule->verb)) {
                continue;
            }
            $rule = [];
            $rule['url'] = str_replace(['<', '>'], ['{', '}'], rtrim($templateObject->getValue($generatedRule), '/'));
            $rule['method'] = current($generatedRule->verb);
            preg_match_all('/\{[^}]*\}/', $rule['url'], $matched);

            $params = [];
            if (!empty($matched[0])) {
                foreach ($matched[0] as $key) {
                    $name = str_replace(['{', '}'], '', $key);
                    $params[] = [
                        'key' => $key,
                        'name' => $name,
                        'title' => $name == 'id' ? 'ID' : ucfirst(str_replace('_', ' ', $name)),
                    ];
                }
            }

            $rule['params'] = $params;

            list($controller, $actionID) = \Yii::$app->createController($generatedRule->route);

            try {
                $methodName = 'action' . BaseInflector::id2camel($actionID);
                $controllerReflection = new \ReflectionClass($controller);
                $methodInfo = $controllerReflection->getMethod($methodName);
                $fieldsString = $this->_findString($methodInfo->getDocComment(), 'Rest Fields');
                if ($fieldsString) {
                    $fieldsOptions = explode('||', $fieldsString);
                    foreach ($fieldsOptions as $fieldsOption) {
                        eval('$rule[\'fields\'][] = ' . $fieldsOption . ';');
                    }
                }
                $rule['filters'] = $this->_findElements($methodInfo->getDocComment(), 'Rest Filters');
                $rule['expand'] = $this->_findElements($methodInfo->getDocComment(), 'Rest Expand');
                $rule['description'] = $this->_findString($methodInfo->getDocComment(), 'Rest Description');
            } catch (\Exception $ex) {
                // Silence, because we do not require description of REST
                // ActiveController method. TODO: add some warning.
            }

            if (!empty($rule['fields'])) {
                $fileFields = [];
                $rule['fields'] = $this->_fieldsFlip($rule['fields'], $fileFields);
                $rule['fileFields'] = $fileFields;
            }

            $rules[] = $rule;
        }

        usort($rules, function ($a, $b) {
            return strcmp($a['url'], $b['url']);
        });

        return $rules;
    }

    function _fieldsFlip($fields, &$fileFields = [])
    {
        $flipped = [];
        foreach ($fields as $key => $field) {
            if (is_array($field)) {
                $flipped[$key] = $this->_fieldsFlip($field, $fileFields);
            } else {
                if (substr($field, 0, 1) == '_') {
                    $field = substr($field, 1);
                } elseif (strpos($field, ':')) {
                    list($fieldName, $fieldType) = explode(':', $field);
                    if ($fieldType == 'file') {
                        $fileFields[] = $fieldName;
                        continue;
                    }
                }
                $flipped[$field] = '';
            }
        }
        return $flipped;
    }

    function _findString($string, $title, $pattern = '[^.]*\.')
    {
        preg_match("/$title:$pattern/", str_replace('*', '', $string), $matched);
        if (!empty($matched[0])) {
            return trim(str_replace($title . ':', '', $matched[0]), ' .');
        }
    }

    function _findElements($string, $title, $pattern = '[^.]*\.')
    {
        $elementsString = $this->_findString($string, $title, $pattern);
        $elements = [];
        if ($elementsString) {
            eval('$elements = ' . $elementsString . ';');
        }
        $finalElements = [];
        if (!empty($elements)) {
            foreach ($elements as $element) {
                $finalElements[] = [
                    'title' => ucfirst(str_replace('_', ' ', $element)),
                    'key' => $element,
                ];
            }
        }
        return $finalElements;
    }
}

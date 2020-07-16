# yii2-rest-api-doc
Simple documentation generator for Yii2 REST applications based on defined API endpoints and actions annotations.

## Installation
 - Run `composer require nostop8/rest-api-doc`;

Alternatively add into `require` section of your `composer.json` following string `"nostop8/rest-api-doc": "*"` and run `composer update`
 - In your application config file inside `modules` section add
```
'modules' => [
  ...
  'documentation' => 'nostop8\yii2\rest_api_doc\Module',
  ...
],
```

 - In your application config file inside `bootstrap` section add:
```
'bootstrap' => [
  ...
  'documentation'
  ...
],
```
Please, note. You may change `documentation` into any other word, which would be better to call documentation for your REST API.

 - Now run your application at `http://<yourappdomain>/documentation` and if you did everything correct, you shoul see something like this: ![alt tag](http://i.imgur.com/uw91eR6.png)
 

## Usage
- First of all you should know that this documentation generator will work only in case you define your REST API endpoints using following principles: http://www.yiiframework.com/doc-2.0/guide-rest-routing.html
- Currently you can define for you endpoints following annotations types, which will be later displayed/provided by API documentation generator:

1. Rest Description: Your endpoint description.
2. Rest Fields: ['field1', 'field2'] or ['field3', 'field4']. (Please, note: `or` and array after it is extra and might be skipped if your service accepts only one type of body)
3. Rest Filters: ['filter1', 'filter2'].
4. Rest Expand: ['expandRelation1', 'expandRelation2'].

- In case you are using CRUD services, which does not require endpoints to be defined (because they are already predefined inside `yii\rest\UrlRule` - http://www.yiiframework.com/doc-2.0/yii-rest-urlrule.html and implemented inside `\yii\rest\ActiveController`) and you still want to add some description, define in your controller empty methods with the same names (e.g. actionCreate, actionUpdate etc.) and add annotations to them as you would do for other actions implemented by you.

## Example of annotations

```
<?php

namespace app\controllers;

class ExampleController extends \yii\rest\ActiveController
{

    /**
     * Rest Description: Your endpoint description.
     * Rest Fields: ['field1', 'field2'].
     * Rest Filters: ['filter1', 'filter2'].
     * Rest Expand: ['expandRelation1', 'expandRelation2'].
     */
    public function actionTest()
    {
        return ['field1', 'field2'];
    }
}

```

As you may see from this example, every annotation starts with its name and collon (`:`) and ends with a dot (`.`). Also the body of every annotation type might consist of PHP array. You must follow this rules in order to define properly documentation description and service testing functionality.

## Extra Tip
While developing API which should be depended on 3-rd party that are not available yet for some reason, it might be useful for you to use an external <a href="https://quickmocker.com">online API mocking tool</a> - <a href="https://quickmocker.com">QuickMocker</a>. It is also useful while integrating your application with webhooks to debug requests that arrive from some 3-rd party while you do not host your application remotely.

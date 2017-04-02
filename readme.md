## Discounts - Cloudoki

By Rafael G. Couto <rafaelgcouto@gmail.com>



## Developed work

In order to develop this practical test, I've implemented a Laravel package with everything necessary to handle the discounts tasks
proposed by the Cloudoki team.



## Installation

- Since this is just a practical test and not for production, it is required to lower the minimum stability of Laravel 
so that you can be able to include this package. In order to do so update you composer.json file of your Laravel project by adding the two lines below:

    
    "minimum-stability" : "dev", 
    "prefer-stable" : true

- Then add the package to your project by running the following command:

    
    composer require rgcouto/discounts "*"

- Copy the cloudoki folder with example data to the project root by running the following command:

    
    cp -R vendor/rgcouto/discounts/cloudoki/* ./


- On routes/web.php add the following line


    Route::get('order/{order}', 'OrderController@processOrder');


- Run


    php artisan make:controller OrderController


- Open OrderController.php and add the following functions


    /**
     *
     * Processes order by loading orders from file
     * @param $order
     */
    public function processOrder($order)
    {

        //Load Order
        $order = json_decode(file_get_contents(base_path('cloudoki/example-orders')."/order".$order.".json"));

        //Apply discounts
        $orderForCheckout = self::applyDiscounts($order);

        //Show end result
        echo json_encode($orderForCheckout);

    }

    /**
    *
    * Using a json representation of an order, determines the discount of the order
    *
    * @param String $order
    * @return String mixed
    */
    private function applyDiscounts($order)
    {
 
         //Load customers data
         $customers = json_decode(file_get_contents(base_path('cloudoki/data')."/customers.json"));
    
         //Load products data
         $products = json_decode(file_get_contents(base_path('cloudoki/data')."/products.json"));
    
         //Associate costumer to order
         $order->customer = array_values(array_filter($customers,
             function ($customer) use ($order)
             {
                 return $customer->id == $order->{'customer-id'};
             }))[0];
    
         //Associate product for each item in order
         $newItems = array();
         foreach ($order->items as $item) {
             $item->product = array_values(array_filter($products,
                 function ($product) use ($item) {
                     return $product->id == $item->{'product-id'};
                 }))[0];
             array_push($newItems, $item);
         }
         $order->items = $newItems;
    
         //Uses custom package to determine discount and returns an updated checkout order
         $returnOrder = new DiscountsCalculator($order);
         return $returnOrder->getOrderWithDiscount();
    }  

## Usage

Open your browser and navigate to the following URL:

    http://YOU_LARAVEL_INSTALLATION_URL/order/1
    
   (this link will calculate the discounts for the order located in cloudoki/example-data/order1.json, you can change the number to check the 
   discounts on other files.)

## Discounts configuration

In the config folder of the package it is possible to find a discount criterion configuration file.

In this file there are 3 discount types pre-configured:
- [discountPercentageByRevenue] A customer who has already bought for over € 1000, gets a discount of 10% on the whole order.
- [freeByAmountOfItemsOfSameCategory] For every products of category "Switches" (id 2), when you buy five, you get a sixth for free.
- [percentageOnCheapestByAmountOfItemsOfSameCategory] If you buy two or more products of category "Tools" (id 1), you get a 20% discount on the cheapest product.

It is possible to add more discounts, just follow the instructions in the criterion.json file.

## Results

By querying an order, the discounts will be calculated, applied and a new order JSON object will be returned. However, this JSON object has a few changes, namely in:

- Each item of each order can now have two new fields: 

    - *discount*: if present, with the discount percentage that was appliedapplied to the item;
    - *free*: if present, free items are to be added to the order.
    
- Each order will can also have:
    
    - *discount-reasons*: if present, stating clearly why and which the discounts were made;
    - *discount*: if present, the discount made on the whole order.
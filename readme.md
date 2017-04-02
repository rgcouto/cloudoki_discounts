## Discounts - Cloudoki

By Rafael G. Couto <rafaelgcouto@gmail.com>



## Developed work

In order to develop this practical test, I've implemented a Laravel package with everything necessary to handle the discounts tasks
proposed by the Cloudoki team.



## Installation

Since this is just a practical test and not for production, it is required to lower the minimum stability of Laravel in order to be able to include this package. 

In order to do so update composer.json of your Laravel project and add the two lines below:
`"minimum-stability" : "dev",`
`"prefer-stable" : true`

Then add the package to your project by running the following command:
    `composer require rgcouto/discounts "*"`


## Discounts configuration

In the config folder of the package it is possible to find a discount criterion configuration file.

In this file there are 3 discount types pre-configured:
- [discountPercentageByRevenue] A customer who has already bought for over € 1000, gets a discount of 10% on the whole order.
- [freeByAmountOfItemsOfSameCategory] For every products of category "Switches" (id 2), when you buy five, you get a sixth for free.
- [percentageOnCheapestByAmountOfItemsOfSameCategory] If you buy two or more products of category "Tools" (id 1), you get a 20% discount on the cheapest product.

It is possible to add more discounts, just follow the instructions in the criterion.json file.
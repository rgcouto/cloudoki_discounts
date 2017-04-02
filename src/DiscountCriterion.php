<?php

namespace rgcouto\Discounts;

class DiscountCriterion
{

    /**
     * Discount percentage by minimum total of order.
     * amount => percentage
     * @var array
     */
    protected $discountPercentageByRevenue = array(
        1000 => 10,
    );

    /**
     * Discount percentage by minimum amount of items of same category
     * category => (minimum => free, [minimum => free])
     * @var array
     */
    protected $freeByAmountOfItemsOfSameCategory = array(
        2 => array(5 => 1),
    );

    /**
     * Discount percentage on cheapest item from category by minimum amount of items
     * category => (minimum => percentage, [minimum => percentage])
     * @var array
     */
    protected $percentageOnCheapestByAmountOfItemsOfSameCategory = array(
        1 => array(2 => 20),
    );

    /**
     * DiscountCriterion constructor.
     * @param string $discountCriterion
     */
    public function __construct($discountCriterion = '')
    {

        // If another source of discount criterion is given
        if ($discountCriterion != "" && is_file($discountCriterion)) {
            $discountCriterionData = file_get_contents($discountCriterion);

            //Update discount criterion
            $discountCriterionData = json_decode($discountCriterionData, true);
            $this->discountPercentageByRevenue = $discountCriterionData["discountPercentageByRevenue"];
            $this->freeByAmountOfItemsOfSameCategory = $discountCriterionData["freeByAmountOfItemsOfSameCategory"];
            $this->percentageOnCheapestByAmountOfItemsOfSameCategory = $discountCriterionData["percentageOnCheapestByAmountOfItemsOfSameCategory"];

        }

    }

    /**
     * Finds discount percentage by revenue of user.
     * @param $revenue
     * @return array
     */
    public function getDiscountPercentageByRevenue($revenue)
    {

        //Reverse sort of discount levels by revenue
        krsort($this->discountPercentageByRevenue, SORT_NUMERIC);

        //Check which discount level the user is in
        foreach ($this->discountPercentageByRevenue as $amount => $percentage) {

            //Revenue of user is greater than discount level, meaning that is entitled to discount
            if ($revenue > $amount)
                return array(
                    "reason" => "You're a loyal customer. You are profiting a ".$percentage."% discount because you already bought a minimum of $amount €.",
                    "percentage" => $percentage
                );
        }

        //Order total is less than minimum discount level
        end($this->discountPercentageByRevenue);
        return array(
            "reason" => "Your order has no discount associated because you have not reached a minimum of ".key($this->discountPercentageByRevenue)." € in orders.",
            "percentage" => 0
        );

    }


    /**
     * Finds amount of free items to add to order if minimum quantity of same category is achieved.
     * @param $categoryAmount
     * @return mixed
     */
    public function getFreeByAmountOfItemsOfSameCategory($categoryAmount)
    {

        //Check if category has freebies
        if (!isset($this->freeByAmountOfItemsOfSameCategory[$categoryAmount["category"]])) {
            //There are no free items for this category
            return array (
                "reason" => "This category has no free items possibility. No free articles were added.",
                "category" => $categoryAmount["category"],
                "free" => 0
            );
        }

        //Finds the minimum order quantity for the category
        $categoryAddForFreeMinimum = $this->freeByAmountOfItemsOfSameCategory[$categoryAmount["category"]];

        krsort($categoryAddForFreeMinimum, SORT_NUMERIC);

        //Check which free level the order is in
        foreach ($categoryAddForFreeMinimum as $amount => $free) {

            //Quantity of same cat. in order is equals to free level, means it's entitled to add free items
            if ($categoryAmount["amount"] == $amount)
                return array(
                    "reason" => "Your order has an amount of ".$categoryAmount["amount"]." items of the same category. ".$free. " item(s) 
                    were added for free to your order on each article of ",
                    "category" => $categoryAmount["category"],
                    "free" => $free
                );
        }

        //Quantity of same cat. is less than minimum free level
        end($categoryAddForFreeMinimum);
        return array (
            "reason" => "Your order has not reached a minimum amount of ".key($categoryAddForFreeMinimum)." items in the same category. No free articles were added.",
            "category" => $categoryAmount["category"],
            "free" => 0
        );
    }

    public function getPercentageOnCheapestByAmountOfItemsOfSameCategory($categoryAmount)
    {

        //Check if category has discount
        if (!isset($this->percentageOnCheapestByAmountOfItemsOfSameCategory[$categoryAmount["category"]])) {
            //There is no discount for this category
            return array (
                "reason" => "This category has no discount possibility.",
                "category" => $categoryAmount["category"],
                "percentage" => 0
            );
        }

        //Finds the minimum order quantity for the category
        $categoryPercentageDiscount = $this->percentageOnCheapestByAmountOfItemsOfSameCategory[$categoryAmount["category"]];

        krsort($categoryPercentageDiscount, SORT_NUMERIC);

        //Check which discount level the order is in
        foreach ($categoryPercentageDiscount as $amount => $percentage) {

            //Quantity of same cat. in order is greater than discount level minimum, means it's entitled to discount of cheapest product
            if ($categoryAmount["amount"] > $amount)
                return array(
                    "reason" => "Your order has an amount of ".$categoryAmount["amount"]." items of the same category. Your order has a 
                    discount of ".$percentage. "% on ",
                    "category" => $categoryAmount["category"],
                    "percentage" => $percentage
                );
        }

        //Quantity of same cat. is less than minimum discount level
        end($categoryPercentageDiscount);
        return array (
            "reason" => "Your order has not reached a minimum amount of ".key($categoryPercentageDiscount)." items 
            in the same category. No discount was added.",
            "category" => $categoryAmount["category"],
            "percentage" => 0
        );

    }



}
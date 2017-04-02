<?php

namespace rgcouto\Discounts;

class DiscountsCalculator {

    private $order;

    private $discountPercentageByRevenue = array();
    private $freeByAmountOfItemsOfSameCategory = array();
    private $percentageOnCheapestByAmountOfItemsOfSameCategory = array();

    private $discountCriterion;

    public function __construct($order) {
        $this->order = $order;
        $this->discountCriterion = new DiscountCriterion(base_path('vendor/rgcouto/discounts/config')."/criterion.json");
    }


    private function applyDiscounts()
    {

        $this->order->{'discount-reasons'} = array();

        //Applies discount on cheapest item
        //Loops through all categories to discount
        foreach($this->percentageOnCheapestByAmountOfItemsOfSameCategory as $discount) {

            //If no discount is given get over
            if ($discount["percentage"] == 0)
                continue;

            $cheapestItem = null;
            //For each item in the order...
            for ($itemN = 0; $itemN < count($this->order->items); $itemN++) {

                //...check if same category as of discount
                if ($this->order->items[$itemN]->product->category == $discount["category"]) {

                    if ($cheapestItem == null) {
                        $cheapestItem = array("itemN" => $itemN, "price" => $this->order->items[$itemN]->{'unit-price'}, "reason" => $discount["reason"]);
                        continue;
                    }

                    //Test cheapest one
                    if ($this->order->items[$itemN]->{'unit-price'} < $cheapestItem["price"])
                        $cheapestItem = array("itemN" => $itemN, "price" => $this->order->items[$itemN]->{'unit-price'}, "reason" => $discount["reason"]);
                }

            }

            //Applies discount on price
            $this->order->items[$cheapestItem["itemN"]]->{'unit-price'} = round($this->order->items[$cheapestItem["itemN"]]->{'unit-price'} * (1 - ($discount["percentage"]/100)), 2);
            //Adds discount % to product
            $this->order->items[$cheapestItem["itemN"]]->discount = $discount["percentage"];
            //Adds discount reason
            array_push($this->order->{'discount-reasons'}, $cheapestItem["reason"]. " the " . $this->order->items[$cheapestItem["itemN"]]->product->description);

        }

        //Adds free items
        //Loops through all categories to give free items
        foreach($this->freeByAmountOfItemsOfSameCategory as $free) {

            //If no free item is given
            if ($free["free"] == 0)
                continue;

            //For each item in the order...
            for ($itemN = 0; $itemN < count($this->order->items); $itemN++) {

                //...check if same category as of free items to give
                if ($this->order->items[$itemN]->product->category == $free["category"]) {
                    //Adds amount of free items to add to order for free
                    $this->order->items[$itemN]->free = $free["free"];
                    //Adds freebie reason
                    array_push($this->order->{'discount-reasons'}, $free["reason"]. " category with reference " . $this->order->items[$itemN]->product->category);

                }

            }
        }

        //Recalculates grand total of the order
        self::reCalculateOrderTotal();

        //Applies global discount on order based on current revenue of user.
        if ($this->discountPercentageByRevenue["percentage"] != 0) {
            //Calculates the grand total of the order with the discount
            $this->order->total = round($this->order->total * (1 - ($this->discountPercentageByRevenue["percentage"]/100)),2);
            //Adds discount % to order
            $this->order->discount = $this->discountPercentageByRevenue["percentage"];
            //Adds discount reason
            array_push($this->order->{'discount-reasons'}, $this->discountPercentageByRevenue["reason"]);
        }

        //Removes user details from order
        unset($this->order->customer);

        //Removes product details from order
        for ($itemN = 0; $itemN < count($this->order->items); $itemN++)
            unset($this->order->items[$itemN]->product);

        //Removes discount reasons if no discount or freebie was given
        if (empty($this->order->{'discount-reasons'})) unset($this->order->{'discount-reasons'});
    }


    /**
     * Determines the discount of the order
     */
    private function determineDiscounts()
    {

        //Will test discount based on user revenue
        /** @noinspection PhpUnusedLocalVariableInspection */
        $this->discountPercentageByRevenue = $this->discountCriterion->getDiscountPercentageByRevenue($this->order->customer->revenue);

        //Gets the amount of items per category
        $amountOfItemsByCategory = self::getAmountOfItemsByCategory();

        //Will get freebies based on amount of products on each order category, if applicable
        foreach ($amountOfItemsByCategory as $key => $amount)
            array_push($this->freeByAmountOfItemsOfSameCategory , $this->discountCriterion->getFreeByAmountOfItemsOfSameCategory(array("category" => $key, "amount" => $amount)));

        //Will get the discount on the cheapest product on each category, if applicable
        foreach ($amountOfItemsByCategory as $key => $amount)
            array_push($this->percentageOnCheapestByAmountOfItemsOfSameCategory , $this->discountCriterion->getPercentageOnCheapestByAmountOfItemsOfSameCategory(array("category" => $key, "amount" => $amount)));

    }


    /**
     * Returns the order with discounts applied
     */
    public function getOrderWithDiscount()
    {
        //Determines the discounts of the order
        self::determineDiscounts();

        //Applies the discounts on the order
        self::applyDiscounts();

        //Returns new order with updated data
        return $this->order;
    }


    /**
     * Recalculates grand total of each product and whole order
     */
    private function reCalculateOrderTotal()
    {
        $grantTotal = 0;

        for ($item = 0; $item < count($this->order->items); $item++) {
            $this->order->items[$item]->total = round($this->order->items[$item]->{'unit-price'} * $this->order->items[$item]->quantity, 2);
            $grantTotal += $this->order->items[$item]->total;
        }

        $this->order->total = $grantTotal;

    }

    /**
     * Gets the amount of items per category
     */
    private function getAmountOfItemsByCategory()
    {

        $amountOfItemsByCategory = array();
        foreach ($this->order->items as $item) {

            if (!isset($amountOfItemsByCategory[$item->product->category]))
                $amountOfItemsByCategory[$item->product->category] = 0;

            $amountOfItemsByCategory[$item->product->category] += $item->quantity;
        }

        return $amountOfItemsByCategory;

    }
}
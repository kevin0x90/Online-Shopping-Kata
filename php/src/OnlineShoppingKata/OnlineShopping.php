<?php
/**
 * Created by PhpStorm.
 * User: luca
 * Date: 28/03/19
 * Time: 16.45
 */

namespace OnlineShoppingKata;

/**
 * Class OnlineShopping
 * @package OnlineShoppingKata
 *
 * The online shopping company owns a chain of Stores selling
 * makeup and beauty products.
 * <p>
 * Customers using the online shopping website can choose a Store then
 * can put Items available at that store into their Cart.
 * <p>
 * If no store is selected, then items are shipped from
 * a central warehouse.
 *
 */
class OnlineShopping
{
    /**
     * @var Session
     */
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * This method is called when the user changes the
     * store they are shopping at in the online shopping
     * website.
     *
     * @param Store|null $storeToSwitchTo
     */
    public function switchStore(Store $storeToSwitchTo = null)
    {
        /** @var Cart $cart */
        $cart = $this->session["CART"];
        $deliveryInformation = $this->session["DELIVERY_INFO"];
        if ($storeToSwitchTo == null) {
            if ($cart != null) {
                foreach ($cart->getItems() as $item) {
                    /** @var Item $item */
                    if("EVENT" === $item->getType()) {
                        $cart->markAsUnavailable($item);
                    }
                }
            }
            if ($deliveryInformation != null) {
                /** @var DeliveryInformation $deliveryInformation */
                $deliveryInformation->setType("SHIPPING");
                $deliveryInformation->setPickupLocation(null);
            }
        } else {
            if ($cart != null) {
                $newItems = [];
                $weight = 0;
                foreach ($cart->getItems() as $item) {
                    /** @var Item $item */
                    if ("EVENT" === $item->getType()) {
                        if ($storeToSwitchTo->hasItem($item)) {
                            $cart->markAsUnavailable($item);
                            $newItems[] = $storeToSwitchTo->getItem($item->getName());
                        } else {
                            $cart->markAsUnavailable($item);
                        }
                    } else if (! $storeToSwitchTo->hasItem($item)) {
                        $cart->markAsUnavailable($item);
                    }
                    $weight += $item->getWeight();
                }

                foreach ($cart->getUnavailableItems() as $unavailableItem) {
                    /** @var Item $unavailableItem */
                    $weight -= $unavailableItem->getWeight();
                }

                /** @var DeliveryInformation $deliveryInformation */
                /** @var Store $currentStore */
                $currentStore = $this->session["STORE"];
                if ($deliveryInformation != null
                    && $deliveryInformation->getType() != null
                    && "HOME_DELIVERY" === $deliveryInformation->getType()
                    && $deliveryInformation->getDeliveryAddress() != null) {
                    if (! $this->session["LOCATION_SERVICE"]->isWithinDeliveryRange($storeToSwitchTo, $deliveryInformation->getDeliveryAddress())) {
                        $deliveryInformation->setType("PICKUP");
                        $deliveryInformation->setPickupLocation($currentStore);
                    } else {
                        $deliveryInformation->setTotalWeight($weight);
                        $deliveryInformation->setPickupLocation($storeToSwitchTo);
                    }
                } else {
                    if ($deliveryInformation != null
                        && $deliveryInformation->getDeliveryAddress() != null) {
                        if ($this->session["LOCATION_SERVICE"]->isWithinDeliveryRange($storeToSwitchTo, $deliveryInformation->getDeliveryAddress())) {
                            $deliveryInformation->setType("HOME_DELIVERY");
                            $deliveryInformation->setTotalWeight($weight);
                            $deliveryInformation->setPickupLocation($storeToSwitchTo);

                        }
                    }
                }
                foreach ($newItems as $item) {
                    $cart->addItem($item);
                }
            }
        }
        $this->session["STORE"] = $storeToSwitchTo;
        $this->session->saveAll();
    }

    public function toString()
    {
        return "OnlineShopping{\n"
            . "session=" . $this->session->toString() . "\n}";
    }
}
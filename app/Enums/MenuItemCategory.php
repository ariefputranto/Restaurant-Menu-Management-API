<?php

namespace App\Enums;

enum MenuItemCategory: string
{
    case Appetizer = 'appetizer';
    case Main = 'main';
    case Dessert = 'dessert';
    case Drink = 'drink';
}

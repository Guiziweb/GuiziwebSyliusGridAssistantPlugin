@grid_assistant_search @javascript @ui
Feature: Searching orders using AI assistant
    In order to find specific orders quickly
    As an Administrator
    I want to use natural language to filter the orders grid

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt"
        And the store has a product "Sylius Book"
        And the store ships everywhere for Free
        And the store also allows shipping with "DHL Express"
        And the store allows paying with "Cash on Delivery"
        And I am logged in as an administrator

    Scenario: Filtering orders by customer email
        Given there is a customer account "john.doe@gmail.com"
        And there is a customer "john.doe@gmail.com" that placed an order "#00000001"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        And there is a customer account "jane.doe@gmail.com"
        And there is a customer "jane.doe@gmail.com" that placed an order "#00000002"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        When I browse orders
        And I search for "orders from john.doe@gmail.com" using the AI assistant
        Then I should see a single order from customer "john.doe@gmail.com"

    Scenario: Filtering orders by state
        Given there is a customer account "john.doe@gmail.com"
        And there is a customer "john.doe@gmail.com" that placed an order "#00000001"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        And there is a "completed" "#00000002" order with "PHP T-Shirt" product
        When I browse orders
        And I search for "new orders" using the AI assistant
        Then I should see a single order from customer "john.doe@gmail.com"

    Scenario: Filtering orders by order number
        Given there is a customer account "john.doe@gmail.com"
        And there is a customer "john.doe@gmail.com" that placed an order "#00000022"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        And there is a customer account "jane.doe@gmail.com"
        And there is a customer "jane.doe@gmail.com" that placed an order "#00000023"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        When I browse orders
        And I search for "order #00000022" using the AI assistant
        Then I should see a single order from customer "john.doe@gmail.com"

    Scenario: Filtering orders by product
        Given there is a customer account "john.doe@gmail.com"
        And there is a customer "john.doe@gmail.com" that placed an order "#00000001"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        And there is a customer account "jane.doe@gmail.com"
        And there is a customer "jane.doe@gmail.com" that placed an order "#00000002"
        And the customer bought a single "Sylius Book"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        When I browse orders
        And I search for "orders containing PHP T-Shirt" using the AI assistant
        Then I should see a single order from customer "john.doe@gmail.com"

    Scenario: Filtering orders by shipping method
        Given there is a customer account "john.doe@gmail.com"
        And there is a customer "john.doe@gmail.com" that placed an order "#00000001"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "Free" shipping method to "United States" with "Cash on Delivery" payment
        And there is a customer account "jane.doe@gmail.com"
        And there is a customer "jane.doe@gmail.com" that placed an order "#00000002"
        And the customer bought a single "PHP T-Shirt"
        And the customer chose "DHL Express" shipping method to "United States" with "Cash on Delivery" payment
        When I browse orders
        And I search for "orders shipped with DHL Express" using the AI assistant
        Then I should see a single order from customer "jane.doe@gmail.com"
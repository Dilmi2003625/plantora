# Week 08: Database Integration & Admin Dashboard

## 1. Introduction
Week 08 focused on establishing permanent persistence for the Plantora E-Commerce project by integrating a central MySQL database. Moving beyond static implementations, MySQL now acts as the main source of truth for application data. This week introduced a secure, role-based admin environment to manage products, variations, inventory, and orders while seamlessly supporting all customer-facing workflows.

## 2. Week 08 Objectives
The primary objectives implemented this week include:
- Connect Plantora to MySQL.
- Store products and variations in the database.
- Store customer and order information persistently.
- Implement admin-only access.
- Create an admin dashboard.
- Implement product CRUD (Create, Read, Update).
- Manage product variations.
- Manage inventory/stock.
- Manage order statuses.
- Maintain data consistency.
- Test customer and admin workflows.
- Apply basic security practices.

## 3. Database Schema
The existing database schema actively used during this integration includes the following core tables:

### `users`
Stores all account information and determines access privileges.
- `user_id`: Primary key
- `name`: User's full name
- `email`: Login credential
- `password`: Hashed password
- `phone`: Contact number
- `address`: Delivery address
- `role`: Enum (`customer`, `admin`) restricting dashboard access
- `created_at`: Account creation timestamp

### `categories`
Organizes the plant and pot offerings.
- `category_id`: Primary key
- `category_name`: Unique category label
- `description`: Textual summary

### `products`
The core catalog item definitions.
- `product_id`: Primary key
- `category_id`: Foreign key to `categories`
- `product_name`: Title of the product
- `product_type`: Enum (`Plant`, `Pot`, `Package`)
- `description`: Full description text
- `care_level`: Care requirement metric
- `material`: Pot or package material
- `care_instructions`: Dedicated care instructions text
- `image`: Relative filepath to the product image
- `created_at`: Timestamp

### `product_variations`
Stores individual sellable variants for a given base product.
- `variation_id`: Primary key
- `product_id`: Foreign key linking to `products`
- `color`: Text description of color
- `size`: Text description of size
- `pot_option`: Enum (`Without Pot`, `With Pot`)
- `price`: Decimal price specific to this variation
- `stock_quantity`: Integer representing physical inventory available

### `cart`
Associates an active shopping cart with a specific user.

### `cart_items`
Links individual variations added to a cart.
- `cart_item_id`: Primary key
- `cart_id`: Foreign key to `cart`
- `variation_id`: Foreign key to `product_variations`
- `quantity`: Amount added

### `orders`
Stores confirmed purchases. Contains fields like `order_id`, `user_id`, `order_date`, `delivery_address`, `delivery_charge`, `subtotal`, `total_amount`, and `order_status` (Enum spanning `Pending` to `Delivered`).

### `order_items`
Records the snapshot of purchased items. Contains `order_item_id`, `order_id`, `variation_id`, `quantity`, `unit_price`, and `subtotal`.

### `payments`
Tracks the payment lifecycle. Contains `payment_id`, `order_id`, `payment_method`, `payment_status` (e.g., `Paid`, `Pending`, `Failed`), and `payment_date`.

## 4. Database Relationships
The database heavily utilizes relational architecture backed by foreign keys to maintain referential integrity:

- **Categories 1 → Many Products** (Each product belongs to one category)
- **Products 1 → Many Product Variations** (One base plant can have multiple sizes/prices)
- **Users 1 → 1 Cart** (Each user manages one active cart)
- **Users 1 → Many Orders** (Customers can make multiple purchases)
- **Orders 1 → 1 Payment** (Each order has a unique payment record)
- **Orders 1 → Many Order Items** (One order consists of multiple line items)
- **Product Variations 1 → Many Cart Items** (Variations exist in multiple users' carts)
- **Product Variations 1 → Many Order Items** (Variations are preserved as line items in purchase history)

## 5. Product Database Integration
Week 08 replaced static array definitions with live MySQL data. 

**Data Flow:**
1. MySQL Database 
2. ↓ PHP/MySQLi Query 
3. ↓ PHP Product Data 
4. ↓ `shop.php` 
5. ↓ Product Cards

The `shop.php` catalog and `product-details.php` views query the `products`, `categories`, and `product_variations` tables. All prices, stock badges, and textual content are read dynamically, ensuring instant synchronization when an admin updates catalog data.

## 6. Checkout & Order Persistence
The checkout system ensures high-integrity financial transactions:

1. Cart
2. ↓ Checkout
3. ↓ Server-side validation
4. ↓ Re-read product/variation price (Prevent client-side spoofing)
5. ↓ Check stock
6. ↓ Calculate subtotal, delivery charge, total
7. ↓ Create `orders` record
8. ↓ Create `order_items` records
9. ↓ Update stock
10. ↓ Order confirmation

Prices and stock quantities are never blindly trusted from client-side POST inputs.

## 7. Inventory / Stock Management
The dedicated `/admin/inventory.php` dashboard facilitates focused stock management.

**Features:**
- Total variations, Total stock units, Low stock count, Out of stock count calculations.
- Live Search (Product, Color, Size).
- Filters (Category, Product Type, Stock Status).
- In-line Stock quantity update forms.

**Stock Status Metric:**
- `> 5` = In Stock
- `1–5` = Low Stock
- `0` = Out of Stock

Inventory queries use `product_variations.stock_quantity` directly, meaning manual admin stock changes execute immediately against the single source of truth database table.

## 8. Admin Authentication & Role-Based Access
Security architecture relies on `users.role`.
- `role === 'admin'` grants access to the dashboard.
- The `/admin/auth.php` guard script is prepended to all admin pages, executing server-side checks.
- If a standard customer or logged-out guest attempts access, execution halts immediately and routes them away, preventing URL-guessing or unauthorized API access.

## 9. Admin Dashboard
`/admin/index.php` serves as the primary hub.

**Features:**
- Dynamic Total Products, Total Orders, Total Revenue, and Low Stock computations.
- Recent Orders table with `order_status` badges.
- Sidebar navigation.
The Low Stock card is hyperlinked directly to the filtered inventory view (`inventory.php?stock_status=low`).

## 10. Product CRUD
Implemented across `/admin/products.php`, `/admin/product-add.php`, and `/admin/product-edit.php`.

**Managed Fields:**
Product name, category, type, description, care level, material, care instructions, and image.
- Image uploads are safely validated against permitted extensions (`JPG`, `PNG`, `WEBP`) and a `< 5MB` size limit.
- Because the existing MySQL `products` table does not possess an `active` or `status` column, a dedicated soft-deactivation feature was omitted in Week 08 to strictly adhere to the rule of not modifying the database schema.

## 11. Product Variation Management
Managed across `/admin/product-variations.php`, `/admin/variation-add.php`, and `/admin/variation-edit.php`.

**Managed Fields:**
Color, Size, Pot option, Price, Stock quantity.
- **Foreign-Key Safety:** The variation deletion script proactively checks `cart_items` and `order_items`. If a variation is tied to historical data or active carts, deletion is aborted, protecting referential integrity.

## 12. Order Management
Implemented in `/admin/orders.php` and `/admin/order-details.php`.

Admins can:
- View the master list of all orders.
- Inspect customer info, delivery addresses, order items, and payment status.
- Securely update the `order_status` Enum (e.g. `Pending` → `Processing`).
Status updates are parameterized and save directly to the `orders` table.

## 13. Security Implementation
The Week 08 integration utilizes robust security measures:
- **password_hash() & password_verify()**: Secure credential storage.
- **PHP Sessions**: Reliable state management.
- **Server-Side Role Checks**: Halting execution for non-admins.
- **Prepared Statements**: Parameterizing all MySQLi inputs to prevent SQL Injection.
- **CSRF Tokens**: All administrative `POST` forms require a session-bound token to execute, mitigating Cross-Site Request Forgery.
- **Server-Side Validation**: Never trusting client stock inputs or checkout prices.
- **Foreign-Key Protection**: Gracefully handling relationships to prevent database orcart corruption upon variation deletion.

## 14. Integration Testing
Final regression testing was performed to guarantee the untouched sections of the platform remain fully functional.

| Test | Result | Description |
|---|---|---|
| Database consistency | PASS | Schema unaltered. Foreign keys functional. |
| Customer authentication | PASS | Login, register, profile workflows remain intact. |
| Admin authentication | PASS | Server-side boundaries fully secure the `/admin/` directory. |
| Product catalog | PASS | Customer shop correctly lists database-driven entries. |
| Product CRUD | PASS | Creation and editing operate smoothly. |
| Variation management | PASS | Variations attach cleanly to parents. |
| Inventory | PASS | Real-time stock queries and direct updates succeed. |
| Cart | PASS | Add-to-cart, quantities, and totals remain functional. |
| Checkout | PASS | Process converts cart to order properly. |
| PayHere | PASS | Notification hooks and payment structures are unbroken. |
| Order creation | PASS | Items transfer cleanly into `orders` and `order_items`. |
| Admin order management | PASS | Status modifications persist correctly. |
| PHP syntax checks | PASS | CLI checks (`php -l`) returned 0 syntax errors across all files. |
| Logout | PASS | Session destruction isolates privileges safely. |

## 15. Debugging / Issues
The strict compartmentalization of the admin scripts resulted in a highly stable launch.
- **Bugs Discovered:** None.
- **Bugs Fixed:** None required, as no regressions occurred in the customer storefront.

## 16. Database Changes
Week 08 did **NOT** modify the database schema.
- No new tables were created.
- No existing tables were dropped.
- No foreign keys were removed.
- Existing data was explicitly preserved.

## 17. Screenshot / Evidence Checklist
*For inclusion in the final academic report, please manually capture the following:*

### Database
- [ ] Screenshot to capture: phpMyAdmin database tables
- [ ] Screenshot to capture: `users` table
- [ ] Screenshot to capture: `categories` table
- [ ] Screenshot to capture: `products` table
- [ ] Screenshot to capture: `product_variations` table
- [ ] Screenshot to capture: `orders` table
- [ ] Screenshot to capture: `order_items` table
- [ ] Screenshot to capture: `payments` table

### Customer Side
- [ ] Screenshot to capture: Shop page with DB-driven products
- [ ] Screenshot to capture: Product details
- [ ] Screenshot to capture: Cart
- [ ] Screenshot to capture: Checkout
- [ ] Screenshot to capture: Order confirmation

### Admin
- [ ] Screenshot to capture: Admin login
- [ ] Screenshot to capture: Admin dashboard
- [ ] Screenshot to capture: Product list
- [ ] Screenshot to capture: Add product
- [ ] Screenshot to capture: Edit product
- [ ] Screenshot to capture: Product variations
- [ ] Screenshot to capture: Inventory page
- [ ] Screenshot to capture: Low stock filter
- [ ] Screenshot to capture: Orders page
- [ ] Screenshot to capture: Order details
- [ ] Screenshot to capture: Updated order status

## 18. Git / Version Control
After reviewing this documentation, the Week 08 changes should be staged and committed manually.

**Recommended commit message:**
```bash
Implemented Week 08 database integration and admin dashboard
```

## 19. Conclusion
Week 08 successfully integrated Plantora with persistent MySQL data. It introduced a highly secure, role-based administrative environment for managing products, variations, inventory, and purchase orders. Crucially, strict adherence to database design constraints ensured that all complex customer-side functionality (including cart sessions and checkout procedures) remained flawlessly operational throughout the integration.

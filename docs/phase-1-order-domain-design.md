# Phase 1: Order Domain Design (No Code)

## 1) Scope
Phase 1 maqsadi: buyurtma jarayonini Google Formdan ichki Order tizimiga o'tkazish.

In scope:
- Order va OrderItem modelini joriy qilish
- Checkout paytida location tanlash (hozircha 2 ta value)
- Payment status va Order statusni alohida boshqarish
- Admin panelda buyurtmalarni to'liq nazorat qilish

Out of scope (Phase 2+):
- Wallet top-up flow
- Payment gateway integratsiyasi
- Courier/fulfillment API integratsiyasi

## 2) Business Requirements
- User buyurtma berganda location tanlaydi:
  - Jizzax shahar
  - Do'stlik tumani
- Buyurtma admin panelga tushadi va admin:
  - to'liq ma'lumotni ko'radi
  - mahsulot rasmlarini ko'radi
  - copy qilinadigan formatni oladi (tikuvchiga yuborish uchun)
  - to'lovni tasdiqlaydi yoki rad etadi

## 3) Proposed Domain Model

### 3.1 Order
Fields:
- id (PK)
- user_id (FK -> user.id)
- customer_full_name (snapshot)
- customer_phone (snapshot)
- location_code (enum-like string)
- location_label (snapshot string)
- notes (nullable)
- subtotal_amount DECIMAL(12,2)
- currency CHAR(3) default UZS
- order_status VARCHAR(30)
- payment_status VARCHAR(30)
- payment_method VARCHAR(30) nullable
- payment_reference VARCHAR(100) nullable
- admin_note TEXT nullable
- approved_by_admin_id FK nullable
- approved_at DATETIME nullable
- created_at DATETIME
- updated_at DATETIME

Indexes:
- idx_order_user_created_at (user_id, created_at)
- idx_order_order_status (order_status)
- idx_order_payment_status (payment_status)
- idx_order_location_code (location_code)

### 3.2 OrderItem
Fields:
- id (PK)
- order_id (FK -> order.id)
- product_id (FK -> product.id)
- product_name_snapshot
- product_image_snapshot
- unit_price DECIMAL(12,2)
- quantity INT
- line_total DECIMAL(12,2)
- created_at DATETIME

Indexes:
- idx_order_item_order (order_id)
- idx_order_item_product (product_id)

## 4) Enums and State Machine

### 4.1 OrderStatus
- new
- payment_pending
- paid
- in_production
- ready
- completed
- canceled

### 4.2 PaymentStatus
- pending
- approved
- rejected

### 4.3 Transition rules (must be validated server-side)
OrderStatus:
- new -> payment_pending
- payment_pending -> paid | canceled
- paid -> in_production
- in_production -> ready
- ready -> completed
- any active state -> canceled (role-restricted)

PaymentStatus:
- pending -> approved | rejected
- approved/rejected -> terminal (no second transition)

Invariant:
- payment_status=approved bo'lmasa order_status paid yoki undan yuqoriga o'tolmaydi.

## 5) Location Model (Phase 1)
Approach: backend whitelist constant (no DB table yet).

Allowed values:
- JIZZAKH_CITY => "Jizzax shahar"
- DUSTLIK_DISTRICT => "Do'stlik tumani"

Validation:
- Frontend select faqat shu ikki qiymatni ko'rsatadi
- Backend har doim whitelist check qiladi
- Noto'g'ri value => 422 validation error

## 6) Application Flow

### 6.1 User Checkout
1. User cartni ko'radi
2. Location tanlaydi
3. Buyurtma yaratadi
4. Cart itemlardan Order + OrderItems yaratiladi
5. Cart tozalanadi faqat DB transaction muvaffaqiyatli bo'lsa
6. User order success sahifaga o'tadi (order raqami bilan)

### 6.2 Admin Review and Payment Approval
1. Admin pending orderlar ro'yxatini ko'radi
2. Order detail sahifasini ochadi (itemlar + product image)
3. Copy blockdan tikuvchi uchun matnni nusxalaydi
4. To'lovni approve/reject qiladi
5. Audit metadata yoziladi (approved_by, approved_at, admin_note)

## 7) Admin UX Requirements

### 7.1 Order List
Columns:
- Order #
- Sana
- Mijoz (ism + tel)
- Location
- Jami summa
- Payment status
- Order status
- Actions (View)

Filters:
- order_status
- payment_status
- location_code
- date range

### 7.2 Order Detail
Sections:
- Customer block
- Delivery/location block
- Items block (name, qty, unit price, line total, image)
- Financial summary
- Payment approval panel
- Copy-for-tailor panel

Copy template (plain text):
- Order raqami
- Mijoz ism/telefon
- Lokatsiya
- Mahsulotlar ro'yxati (nomi + soni)
- Eslatma

## 8) Data Consistency and Transactions
- Order yaratish ACID transactionda bo'lishi shart:
  - create order
  - create items
  - clear cart
- Admin approve/reject ham transactionda bo'lishi shart.
- Double-submitdan himoya:
  - terminal payment status bo'lsa qayta approve/reject taqiqlanadi
  - optimistic lock yoki explicit status guard

## 9) Security and Compliance Baseline
- OWASP ASVS tamoyillari:
  - CSRF all mutating endpoints
  - Authorization check every admin action
  - Server-side validation for all input
- PII minimization:
  - faqat zarur fieldlar saqlansin
  - admin listda masklash optional
- Auditability:
  - admin decision log mandatory

## 10) API/Controller Contracts (Phase 1 target)
- POST /order/place
  - inputs: location_code, notes(optional), csrf
  - output: redirect success with order_id
- GET /admin/orders
  - paginated list + filters
- GET /admin/orders/{id}
  - full detail
- POST /admin/orders/{id}/payment/approve
- POST /admin/orders/{id}/payment/reject

## 11) Test Strategy (International Good Practice)
- Unit tests:
  - status transition guard
  - location validator
  - amount calculations
- Functional tests:
  - checkout success path
  - invalid location path
  - admin approve/reject path
  - duplicate approve blocked
- Regression tests:
  - cart clears only after order persisted

## 12) Acceptance Criteria
- User orderda location tanlay oladi (2 option)
- Order admin listda ko'rinadi
- Order detailda barcha itemlar va rasmlar chiqadi
- Admin copy blockdan matnni bir klikda oladi
- Admin payment approve/reject qila oladi
- Double approve/reject imkoni yo'q
- Testlar green (unit + functional minimum suite)

## 13) Delivery Sequence (Implementation order)
1. Migration + Entity layer (Order, OrderItem)
2. Checkout update (location + order creation transaction)
3. Admin order list/detail
4. Admin payment actions + guards + audit fields
5. Tests and hardening

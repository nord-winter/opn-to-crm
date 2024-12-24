# opn-to-crm
“Plugin OPN TO CRM is a WordPress plugin for integration with OPN Payments via GraphQL API. Supports custom payment forms, order management and secure payment processing. Easily customizable and extensible for your business needs.”



### Core Plugin Architecture:

```bash
opn-to-crm/
├── includes/
│   ├── api/
│   │   ├── class-sr-api.php        # SalesRender GraphQL API handler
│   │   └── class-opn-api.php       # OPN Payments API handler
│   ├── admin/
│   │   ├── class-sr-admin.php      # Admin interface handler
│   │   └── class-sr-settings.php   # Plugin settings management
│   ├── checkout/
│   │   ├── class-sr-checkout.php   # Custom checkout handler
│   │   └── class-sr-payment.php    # Payment processing
│   └── class-sr-core.php           # Core plugin functionality
├── templates/
│   ├── admin/
│   │   └── settings.php            # Admin settings template
│   └── checkout/
│       ├── form.php                # Main checkout form
│       └── payment.php             # Payment form
├── assets/
│   ├── css/
│   │   └── style.css              # Plugin styles
│   └── js/
│       ├── checkout.js            # Checkout functionality
│       └── admin.js               # Admin scripts
└── opn-to-crm.php                 # Main plugin file
```

### Key Features to Implement:

- Custom checkout form with:
    - Package selection (1x, 2x, 3x, 4x)
    - Customer information collection
    - Address/shipping details
    - Payment method selection (Credit Card, PromptPay)
- SalesRender Integration:
    - GraphQL API communication
    - Order creation and management
    - Field mapping configuration
    - Status synchronization
- OPN Payments Integration:
    - Secure payment processing
    - Multiple payment methods
    - 3D Secure support
    - Payment status handling

GraphQL Mutations and Queries Example:

```graphql
mutation CreateOrder($input: AddOrderInput!) {
  orderMutation {
    addOrder(input: $input) {
      id
      status
      created
    }
  }
}

mutation UpdateOrderStatus($input: UpdateOrderStatusInput!) {
  orderMutation {
    updateStatus(input: $input) {
      id
      status
    }
  }
}
```

# OPN Payments Integration Plan

## 1. API Configuration

### Key Credentials Required:

- Public Key (for frontend)
- Secret Key (for backend)
- Test/Live mode toggle
- Webhook Secret
- Default currency (THB)
- Supported Payment Methods:
    - Credit/Debit Cards
    - PromptPay QR
    - (Optional) Installments

### Webhook Endpoints:

- Payment status updates
- Refund notifications
- 3D Secure completion

## 2. Frontend Implementation

### Checkout Form Structure:

1. Personal Information Section:
    - First Name
    - Last Name
    - Email
    - Phone (+66 format)
2. Address Section:
    - Street Address
    - City
    - Postal Code
    - Country (default: Thailand)
3. Package Selection:
    - 1x Package
    - 2x Package (5% discount)
    - 3x Package (10% discount)
    - 4x Package (15% discount)
4. Payment Section:
    - Payment method selection
    - Card form or QR display area
    - Error message container
    - Payment button

### Payment Flow Screens:

1. Method Selection
2. Payment Details Entry
3. Processing State
4. Success/Error State
5. 3D Secure Redirect (if applicable)

### JavaScript Requirements:

1. OPN.js Integration:
    - Script loading
    - Configuration
    - Token/Source generation
2. Form Validation:
    - Real-time field validation
    - Thai phone number format
    - Thai postal code format
    - Required field checks
3. Payment Processing:
    - Card tokenization
    - QR code display
    - Status polling for PromptPay
    - 3D Secure handling
    - Error handling

## 3. Backend Implementation

### Payment Processing:

1. Charge Creation:
    - Amount calculation
    - Currency handling
    - Metadata preparation
    - Customer information formatting
2. Payment Methods Handler:
    - Credit Card processing
    - PromptPay source creation
    - 3D Secure flow management
    - Installment plan handling
3. Status Management:
    - Payment status tracking
    - Order status updates
    - Webhook processing
    - Error handling

### Security Measures:

1. Data Validation:
    - Input sanitization
    - Amount verification
    - Currency validation
    - Token/Source verification
2. API Security:
    - SSL requirement
    - API key protection
    - Webhook signature verification
    - PCI compliance considerations

### Error Handling:

1. Payment Failures:
    - Card declined
    - Insufficient funds
    - Invalid card
    - 3D Secure failed
    - Network errors
2. System Errors:
    - API timeout
    - Invalid response
    - Configuration errors
    - Webhook processing errors

## 4. Integration Points

### OPN API Endpoints:

1. Tokens:
    - Create token (cards)
    - Retrieve token information
2. Sources:
    - Create source (PromptPay)
    - Retrieve source status
3. Charges:
    - Create charge
    - Retrieve charge
    - Update charge
    - Capture charge
4. Refunds:
    - Create refund
    - Retrieve refund

### Webhook Events:

1. Payment Events:
    - charge.create
    - charge.complete
    - charge.expire
    - charge.fail
2. Source Events:
    - source.create
    - source.expire
    - source.fail
3. Refund Events:
    - refund.create
    - refund.fail

## 5. Testing Requirements

### Test Scenarios:

1. Payment Methods:
    - Successful card payment
    - Failed card payment
    - 3D Secure flow
    - PromptPay payment
    - Installment payment
2. Edge Cases:
    - Network interruption
    - Browser refresh
    - Session timeout
    - Invalid inputs
    - API errors

### Test Cards:

- Successful payment
- Failed payment
- 3D Secure required
- Card declined
- Insufficient funds

---

# SalesRender CRM Integration Plan

## 1. API Configuration

### Required Credentials:

- Company ID
- API Token
- Project ID
- Status ID mappings
- Field mappings
- GraphQL API endpoint

### GraphQL Endpoint:

- Base URL: `https://de.backend.salesrender.com/companies/`
- Scopes: `/CRM`, `/CRM/user`
- Authentication: Bearer token

## 2. Data Structure

### Order Data Schema:

1. Customer Information:
    
    ```
    Copy
    humanNameFields:
      - field: "name"
        value:
          firstName: string
          lastName: string
    
    phoneFields:
      - field: "phone"
        value: string
    
    emailFields:
      - field: "email"
        value: string
    
    ```
    
2. Order Details:
    
    ```
    Copy
    cart:
      items:
        - itemId: integer
          quantity: integer
          variation: integer
          price: integer (in cents)
    
    ```
    
3. Source Information:
    
    ```
    Copy
    source:
      refererUri: string
      ip: string
    
    ```
    

### Status Mappings:

- New
- Processing
- In Progress
- Completed
- Failed
- Cancelled
- Refunded

## 3. GraphQL Implementation

### Required Mutations:

1. Create Order:
    
    ```graphql
    graphql
    Copy
    mutation ($input: AddOrderInput!) {
      orderMutation {
        addOrder (input: $input) {
          projectId
          statusId
          created
        }
      }
    }
    
    ```
    
2. Update Status:
    
    ```graphql
    graphql
    Copy
    mutation ($input: UpdateOrderStatusInput!) {
      orderMutation {
        updateStatus (input: $input) {
          id
          status
        }
      }
    }
    
    ```
    
3. Update Order Data:
    
    ```graphql
    graphql
    Copy
    mutation ($input: UpdateOrderInput!) {
      orderMutation {
        updateOrder (input: $input) {
          id
          orderData
        }
      }
    }
    
    ```
    

### Required Queries:

1. Get Order:
    
    ```graphql
    graphql
    Copy
    query ($id: ID!) {
      order(id: $id) {
        id
        status
        orderData
        created
      }
    }
    
    ```
    
2. Get Order Status:
    
    ```graphql
    graphql
    Copy
    query ($id: ID!) {
      order(id: $id) {
        id
        status
      }
    }
    
    ```
    

## 4. Backend Implementation

### Core Components:

1. API Handler:
    - GraphQL request builder
    - Response parser
    - Error handling
    - Rate limiting
    - Retry logic
2. Data Mapper:
    - Field mapping configuration
    - Data transformation
    - Validation rules
    - Format conversion
3. Order Manager:
    - Order creation
    - Status updates
    - Data synchronization
    - Error recovery
4. Status Handler:
    - Status mapping
    - State transitions
    - Event triggers
    - Notifications

### Error Handling:

1. API Errors:
    - Network failures
    - Authentication errors
    - Authorization errors
    - Rate limit exceeded
    - Invalid requests
2. Data Validation:
    - Required fields
    - Format validation
    - Business rules
    - Data consistency
3. System Errors:
    - Configuration issues
    - Connection timeouts
    - Server errors
    - Database errors

## 5. Integration Points

### Order Creation Flow:

1. Data Collection:
    - Form submission
    - Data validation
    - Field mapping
2. Order Processing:
    - Create order in SalesRender
    - Store reference ID
    - Handle response
    - Update local status
3. Status Updates:
    - Monitor status changes
    - Update SalesRender
    - Sync local status
    - Trigger notifications

### Error Recovery:

1. Failed Orders:
    - Retry mechanism
    - Error logging
    - Admin notifications
    - Manual intervention
2. Data Inconsistency:
    - Validation checks
    - Data repair
    - Sync verification
    - Audit logging

## 6. Testing Requirements

### Test Scenarios:

1. Order Operations:
    - Create new order
    - Update order status
    - Modify order data
    - Cancel order
    - Refund handling
2. Data Validation:
    - Required fields
    - Field formats
    - Data types
    - Business rules
3. Error Handling:
    - API errors
    - Network issues
    - Invalid data
    - Rate limiting
4. Integration:
    - Payment sync
    - Status sync
    - Data consistency
    - Event handling

### Test Environment:

- Test company account
- Test API credentials
- Sandbox environment
- Test data sets

## 7. Monitoring and Logging

### System Monitoring:

1. API Health:
    - Response times
    - Error rates
    - Rate limit usage
    - Success rates
2. Data Sync:
    - Sync status
    - Failed syncs
    - Data integrity
    - Performance metrics

### Logging Requirements:

1. API Interactions:
    - Request/response
    - Errors
    - Timing
    - Status codes
2. Order Processing:
    - Status changes
    - Data updates
    - Error conditions
    - System events
3. Error Tracking:
    - Error types
    - Stack traces
    - Context data
    - Resolution status

### Code examples for order creation (for CRM API) - **Примеры кода на создание заказа (для API CRM)**

• **Запрос для клиента GraphQL (для API CRM):**

```graphql
mutation {
  orderMutation {
    addOrder(input: {
      statusId: "19",
      projectId: "3",
      orderData: {
        humanNameFields: [
          {
            field: "name1",
            value: {
              firstName: "John",
              lastName: "Doe"
            }
          }
        ],
        phoneFields: [
          {
            field: "phone",
            value: "+66911111111"
          }
        ],
        addressFields: [
          {
            field: "adress",
            value: {
              postcode: "1111",
              region: "Region Test",
              city: "Test City"
              address_1: "Test adress field"
            }
          }
        ]
      },
      cart: {
        items: [
          {
            itemId: 38,
            quantity: 1,
            variation: 1
          }
        ]
      },
      source: {
        refererUri: "http://test.com",
        ip: "127.0.0.1"
      }
    }) {
      id
    }
  }
}

# Result

{
  "data": {
    "orderMutation": {
      "addOrder": {
        "id": "139"
      }
    }
  }
}

# Mutation order

mutation {
  orderMutation {
    updateOrder(input: {
      id: "139", # ID заказа, который вы хотите обновить
      statusId: "20" # Новый статус заказа
    }) {
      id
      status {
        id
        name
      }
      updatedAt
      statusChangedAt
    }
  }
}
```

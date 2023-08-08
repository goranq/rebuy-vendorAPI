# API call and response samples

### Invalid logins

#### No token supplied

Request:
```bash
curl --request GET \
  --url http://localhost/product/
```

Response (`HTTP 400`):
```json
{
	"message": "No API token supplied."
}
```

#### Wrong token supplied

Request:
```bash
curl --request GET \
  --url http://localhost/product/ \
  --header 'Authorization: Bearer 64d282552fa71'
```

Response (`HTTP 401`):
```json
{
	"message": "Supplied API token is not valid."
}
```

### `GET /product/` - get collection of products (all products in the database)

Valid request:
```bash
curl --request GET \
  --url http://localhost/product/ \
  --header 'Authorization: Bearer 64d282552fa72'
```
  
Valid response (`HTTP 200`):
```json
[
	{
		"productID": 1,
		"productEANCodes": "4873471944462",
		"productName": "EcoChill Air Cooler",
		"productManufacturer": "EcoLuxe",
		"productCategory": "Home Appliances",
		"productPrice": 28.35
	},
	{
		"productID": 2,
		"productEANCodes": "2524030711294",
		"productName": "LuxeGlow Facial Serum",
		"productManufacturer": "GlowTech",
		"productCategory": "Skincare",
		"productPrice": 172.88
	},
    ...
    {
		"productID": 50,
		"productEANCodes": "1624848862525",
		"productName": "CosmicView Telescope",
		"productManufacturer": "CosmicView",
		"productCategory": "Aromatherapy",
		"productPrice": 41.12
	}
]
```
### `GET /product/{id}` - get product identified by `{id}`

Valid request:
```bash
curl --request GET \
  --url http://localhost/product/50 \
  --header 'Authorization: Bearer 64d282552fa72'
```
  
Valid response (`HTTP 200`):
```json
{
	"productID": 50,
	"productEANCodes": "1624848862525",
	"productName": "CosmicView Telescope",
	"productManufacturer": "CosmicView",
	"productCategory": "Aromatherapy",
	"productPrice": 41.12
}
```

Invalid request:
```bash
curl --request GET \
  --url http://localhost/product/51 \
  --header 'Authorization: Bearer 64d282552fa72'
```

Error response (`HTTP 404`):
```json
{
	"message": "Could not find product with ID 51 in database."
}
```

### `POST /product/` - create a new product (product data in JSON format)
Valid request:
```bash
curl --request POST \
  --url http://localhost/product \
  --header 'Authorization: Bearer 64d282552fa72' \
  --header 'Content-Type: application/json' \
  --data '{
	"productEANCodes": "3210987654321",
	"productName": "Saturn V",
	"productManufacturer": "NASA",
	"productCategory": "Space Exploration",
	"productPrice": 199999999.99
}'
```

Valid response (`HTTP 201`):
```json
{
	"productID": 51,
	"productEANCodes": "3210987654321",
	"productName": "Saturn V",
	"productManufacturer": "NASA",
	"productCategory": "Space Exploration",
	"productPrice": 199999999.99
}
```

Invalid request:
```bash
curl --request POST \
  --url http://localhost/product \
  --header 'Authorization: Bearer 64d282552fa72' \
  --header 'Content-Type: application/json' \
  --data '{
	"productEANCodes": "3210987654",
	"productName": "Saturn V",
	"productManufacturer": "NASA",
	"productCategory": "",
	"productPrice": 199999999.99
}'
```

Error response (`HTTP 400`):
```json
{
	"message": "Validation of product data failed.",
	"validationErrors": [
		"EAN Code(s) can't be shorter than 13 digits.",
		"Product category can't be blank."
	]
}
```

### `PUT /product/{id}` - update a product identified by `{id}` (product data in JSON format)

Valid request:
```bash
curl --request PUT \
  --url http://localhost/product/51 \
  --header 'Authorization: Bearer 64d282552fa72' \
  --header 'Content-Type: application/json' \
  --data '{
	"productEANCodes": "1234567890124",
	"productName": "Saturn X"
}'
```

Valid response (`HTTP 200`):
```json
{
	"productID": 51,
	"productEANCodes": "1234567890124",
	"productName": "Saturn X",
	"productManufacturer": "NASA",
	"productCategory": "Space Exploration",
	"productPrice": 199999999.99
}
```

Invalid request:
```bash
curl --request PUT \
  --url http://localhost/product/51 \
  --header 'Authorization: Bearer 64d282552fa72' \
  --header 'Content-Type: application/json' \
  --data '{
	"productEANCodes": "123456789",
	"productName": "Saturn X"
}'
```

Error response (`HTTP 400`):
```json
{
	"message": "Validation of product data failed.",
	"validationErrors": [
		"EAN Code(s) can't be shorter than 13 digits."
	]
}
```

Invalid request:
```bash
curl --request PUT \
  --url http://localhost/product/52 \
  --header 'Authorization: Bearer 64d282552fa72' \
  --header 'Content-Type: application/json' \
  --data '{
	"productEANCodes": "1234567890123",
	"productName": ""
}'
```

Error response (`HTTP 400`):
```json
{
	"message": "Product with ID 52 could not be updated."
}
```

### `DELETE /product/{id}` - delete a product identified by `{id}`

Valid request:
```bash
curl --request DELETE \
  --url http://localhost/product/51 \
  --header 'Authorization: Bearer 64d282552fa72'
```

Valid response (`HTTP 200`):
```json
{
	"success": "Product with ID 51 successfully deleted."
}
```

Invalid request:
```bash
curl --request DELETE \
  --url http://localhost/product/52 \
  --header 'Authorization: Bearer 64d282552fa72'
```

Error Response (`HTTP 400`):
```json
{
	"message": "Product with ID 52 could not be deleted."
}
```
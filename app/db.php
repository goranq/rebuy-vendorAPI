<?php

class Database {
    private $db;
    private $validationErrorMessage;

    public function connect() {
        try {
            $this->db = new PDO('sqlite:rebuy.sqlite');
            return true;
        } catch (PDOException $ex) {
            // $ex->errorInfo[1] Driver specific error code
            // $ex->errorInfo[2] Driver specific error message
            error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);
            
            return false;
        }
    }

    public function disconnect() {
        $this->db = null;
    }

    public function getValidationErrorMessage() {
        return $this->validationErrorMessage;
    }

    public function validateProductData($arrProductData) {
        $this->validationErrorMessage = null;
        $validationResult = null;

        foreach ($arrProductData as $key => $value) {
            switch ($key) {
                case "productEANCodes":
                    if (strlen($value) < 13) {
                        $this->validationErrorMessage[] = "EAN Code(s) can't be shorter than 13 digits.";
                    }
                    break;

                case "productName":
                    if (strlen($value) < 1) {
                        $this->validationErrorMessage[] = "Product name can't be blank.";
                    }
                    break;
                
                case "productManufacturer":
                    if (strlen($value) < 1) {
                        $this->validationErrorMessage[] = "Product manufacturer's name can't be blank.";
                    }
                    break;

                case "productCategory":
                    if (strlen($value) < 1) {
                        $this->validationErrorMessage[] = "Product category can't be blank.";
                    }
                    break;

                case "productPrice":
                    if (!is_numeric($value)) {
                        $this->validationErrorMessage[] = "Product price must be numeric value.";
                    }
                    break;
                
                default:
                    // Ideally, this code should not run :)
                    $this->validationErrorMessage[] = "Unknow validation error occured.";
                    break;
            }
        }

        // in case of validation errors, build a response
        if (!empty($this->validationErrorMessage)) {
            $validationResult = array(
                "message" => "Validation of product data failed.",
                "validationErrors" => array());

            foreach ($this->validationErrorMessage as $error) {
                $validationResult["validationErrors"][] = $error;
            }
        }

        return $validationResult;
    }

    public function getAllProducts() {
        $getAllProductsSQL = "SELECT * FROM products;";

        try {
            $result = $this->db->query($getAllProductsSQL)->fetchAll(PDO::FETCH_ASSOC);

            //Something went wrong
            if ($result == false) {
                return array("message" => "Could not return products due to database error.");
            }

            return $result;
        } catch (PDOException $ex) {
            // $ex->errorInfo[1] Driver specific error code
            // $ex->errorInfo[2] Driver specific error message
            error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);
            
            return array("message" => "Could not return products due to database error.");
        }
    }

    public function getProduct($productID) {
        $getProductSQL = "SELECT * FROM products WHERE productID = ?";

        try {
            $statement = $this->db->prepare($getProductSQL);
            $statement->execute(array($productID));
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            // Something went wrong
            if ($row == false) {
                return array("message" => "Could not find product with ID $productID in database.");
            }

            return $row;
        } catch (PDOException $ex) {
            // $ex->errorInfo[1] Driver specific error code
            // $ex->errorInfo[2] Driver specific error message
            error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);
            
            return array("message" => "Could not return product due to database error.");
        }
    }

    public function addProduct(
        $productEANCodes,
        $productName,
        $productManufacturer,
        $productCategory,
        $productPrice
        ) {
            // Make an array for easier use in $statement->execute()
            $arrNewProductData = array(
                "productEANCodes" => $productEANCodes,
                "productName" => $productName,
                "productManufacturer" => $productManufacturer,
                "productCategory" => $productCategory,
                "productPrice" => $productPrice
            );

            // Check if product data is valid
            $validation = $this->validateProductData($arrNewProductData);
            if (!empty($validation)) {
                return $validation;
            }
            
            // Generate and execute PDO prepared statement to insert product data
            $addProductSQL = "INSERT INTO products 
                (productEANCodes,
                productName,
                productManufacturer,
                productCategory,
                productPrice)  
                VALUES 
                (:productEANCodes,
                :productName,
                :productManufacturer,
                :productCategory,
                :productPrice);";
            
            try {
                $statement = $this->db->prepare($addProductSQL);
                $statement->execute($arrNewProductData);

                // Something went wrong
                if ($statement->rowCount() != 1) {
                    return array("message" => "Product could not be created due to database error.");
                }

                // All went well, return newly added product data
                return $this->getProduct($this->db->lastInsertId());
            } catch (PDOException $ex) {
                // $ex->errorInfo[1] Driver specific error code
                // $ex->errorInfo[2] Driver specific error message
                error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);
                
                return array("message" => "Product could not be created due to database error.");
            }
    }

    public function updateProduct($productID, $arrUpdateData) {

        // Check if product data is valid
        $validation = $this->validateProductData($arrUpdateData);
        if (!empty($validation)) {
            return $validation;
        }

        // Generate SQL for PDO prepared statement to update product data
        $updateProductSQL = "UPDATE products SET ";  
        foreach ($arrUpdateData as $field => $value) {
            // First field, no need for leading comma
            if ($updateProductSQL == "UPDATE products SET ") {
                $updateProductSQL.= " $field = :$field";
            }
            else {
                $updateProductSQL.= ", $field = :$field";
            }
        }
        $updateProductSQL.= " WHERE productID = $productID;";
        
        try {
            $statement = $this->db->prepare($updateProductSQL);
            $result = $statement->execute($arrUpdateData);

            // Something went wrong
            if ($statement->rowCount() != 1) {
                return array("message" => "Product with ID $productID could not be updated.");
            }

            // All went well, return updated product data
            return $this->getProduct($productID);
        } catch (PDOException $ex) {
            // $ex->errorInfo[1] Driver specific error code
            // $ex->errorInfo[2] Driver specific error message
            error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);
            
            return array("message" => "Product could not be updated due to database error.");
        }
    }

    public function deleteProduct($productID) {
        $deleteProductSQL = "DELETE FROM products WHERE productID = ?";

        try {
            $statement = $this->db->prepare($deleteProductSQL);
            $statement->execute(array($productID));

            if ($statement->rowCount() == 1) {
                return array("success" => "Product with ID $productID successfully deleted.");
            }
            else {
                return array("message" => "Product with ID $productID could not be deleted.");
            }
        } catch (PDOException $ex) {
            // $ex->errorInfo[1] Driver specific error code
            // $ex->errorInfo[2] Driver specific error message
            error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);
            
            return array("message" => "Product could not be deleted due to database error.");
        }
    }

    public function checkToken($userToken) {
        // Remove "Bearer" part
        // $token[0] = "Bearer"
        // $token[1] = <token_value>
        $token = explode(" ", $userToken);
        $authSQL = "SELECT * FROM users WHERE token = ?";

        try {
            $statement = $this->db->prepare($authSQL);
            $statement->execute(array($token[1]));
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            // Returns user info array, or false if not found/error
            return $row;
        } catch (PDOException $ex) {
            // $ex->errorInfo[1] Driver specific error code
            // $ex->errorInfo[2] Driver specific error message
            error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);
            
            return false;
        }
    }

    public function _bootstrapDB() {
        $productManufacturers = [
            "EcoLuxe",
            "GlowTech",
            "ZenFit",
            "CrispSound",
            "AquaTunes",
            "GloBeauty",
            "SwiftFly",
            "CocoBlend",
            "FlexiSport",
            "NovaGuard",
            "SwiftSlice",
            "UrbanTrek",
            "SolarGlow",
            "DreamEase",
            "HydraGreen",
            "BlissAroma",
            "TechScribe",
            "ZenSecure",
            "SculptFlex",
            "CasaNova",
            "RapidCharge",
            "LunaGlow",
            "EverFlame",
            "ChromaView",
            "FrostGuard",
            "GourmetGrind",
            "FuzionPro",
            "NovaLuxe",
            "SonicSmile",
            "VerdeVue",
            "TempoTune",
            "VitaBlend",
            "GlideSwift",
            "SolarFlare",
            "PulseWave",
            "HydroCrisp",
            "CuddleNest",
            "PawsomeTrail",
            "BlazeBite",
            "SonicGlow",
            "InnovaRide",
            "AeroShift",
            "LumaSync",
            "TerraTrek",
            "TangleFix",
            "SleekNova",
            "GloWave",
            "NatureZen",
            "NovaPulse",
            "CosmicView"
        ];
        $productNames = [
            "EcoChill Air Cooler",
            "LuxeGlow Facial Serum",
            "Zenith Fitness Tracker",
            "CrispTech Wireless Earbuds",
            "AquaWave Waterproof Speaker",
            "GloSoft Makeup Brushes Set",
            "SwiftJet Drone Pro",
            "CocoBrew Cold Brew Maker",
            "FlexiFit Resistance Bands",
            "NovaTech Smart Doorbell",
            "SwiftSlice Food Chopper",
            "UrbanHike Backpack",
            "SolarBloom Garden Lights",
            "DreamScape Sleep Mask",
            "HydraPod Plant Waterer",
            "BlissfulBreeze Aromatherapy Diffuser",
            "TechScribe Stylus Pen",
            "ZenGuard Home Security System",
            "SculptFlex Yoga Mat",
            "CasaNova Smart Thermostat",
            "RapidCharge Power Bank",
            "LunaSync Moon Lamp",
            "EverSpark Fire Starter",
            "ChromaView VR Headset",
            "FrostGuard Car Windshield Cover",
            "GourmetGrind Coffee Grinder",
            "Fuzion Pro Gaming Mouse",
            "NovaLuxe Luggage Set",
            "SonicRise Electric Toothbrush",
            "VerdeVue Blue Light Glasses",
            "TempoTune Metronome",
            "VitaBite Blender",
            "GlideSwift Electric Scooter",
            "SolarFlare Portable Solar Charger",
            "PulseWave Massage Gun",
            "HydroCrisp Plant Grow Lights",
            "CuddleNest Pregnancy Pillow",
            "PawsomeTrail Pet Carrier",
            "BlazeBite BBQ Grill",
            "SonicGlow Facial Cleanser",
            "InnovaRide Electric Skateboard",
            "AeroShift Bike Helmet",
            "LumaSync Smart Light Bulbs",
            "TerraTrek Camping Tent",
            "TangleFix Hair Detangler",
            "SleekNova Hair Straightener",
            "GloWave Self-Tanning Mousse",
            "NatureZen Essential Oils Set",
            "NovaPulse Fitness Tracker",
            "CosmicView Telescope"
        ];
        $productCategories = [
            "Home Appliances",
            "Skincare",
            "Fitness",
            "Audio",
            "Outdoor",
            "Beauty",
            "Drones",
            "Kitchen Appliances",
            "Fitness Accessories",
            "Home Security",
            "Kitchen Gadgets",
            "Outdoor Gear",
            "Garden Accessories",
            "Sleep Accessories",
            "Gardening",
            "Aromatherapy",
            "Office Supplies",
            "Home Security",
            "Yoga & Fitness",
            "Home Appliances",
            "Power Banks",
            "Home Decor",
            "Camping Essentials",
            "Fire Starters",
            "Virtual Reality",
            "Automotive Accessories",
            "Kitchen Appliances",
            "Gaming Accessories",
            "Travel Gear",
            "Oral Care",
            "Eyewear",
            "Musical Instruments",
            "Kitchen Appliances",
            "Electric Scooters",
            "Solar Chargers",
            "Massage Therapy",
            "Gardening",
            "Pregnancy & Maternity",
            "Pet Accessories",
            "Outdoor Cooking",
            "Skincare",
            "Outdoor Recreation",
            "Sports Gear",
            "Bike Accessories",
            "Smart Lighting",
            "Camping Gear",
            "Hair Care",
            "Hair Care",
            "Self-Tanning",
            "Aromatherapy",
            "Fitness Accessories",
            "Telescopes"
        ];
        
        if ($this->connect() == false) {
            return false;
        }

        try {
            // Create user table and default user
            $createUserTableSQL = "CREATE TABLE IF NOT EXISTS users (
                userID INTEGER NOT NULL PRIMARY KEY,
                username TEXT NOT NULL,
                passwordHash TEXT NOT NULL,
                token TEXT 
                );";
            $this->db->query($createUserTableSQL);

            $countUserRowsSQL = "SELECT COUNT(userID) AS rowCount FROM users;";
            $row = $this->db->query($countUserRowsSQL)->fetch();
            if ($row["rowCount"] > 0) {
                return true;
            }

            // Create pass hash and insert default user data in DB
            $passHash = password_hash("rocks", PASSWORD_DEFAULT);
            $token = uniqid();
            $insertUserDataSQL = "INSERT INTO users 
                (username,
                passwordHash,
                token)  
                VALUES 
                ('rebuy',  
                '$passHash',
                '$token');";
            $this->db->query($insertUserDataSQL);

            // Create products table SQL
            $createProductTableSQL = "CREATE TABLE IF NOT EXISTS products (
            productID INTEGER NOT NULL PRIMARY KEY,
            productEANCodes TEXT,
            productName TEXT,
            productManufacturer TEXT,
            productCategory TEXT,
            productPrice REAL 
            ); ";
            $this->db->query($createProductTableSQL);

            $countRowsSQL = "SELECT COUNT(productID) AS rowCount FROM products;";
            $row = $this->db->query($countRowsSQL)->fetch();
             if ($row["rowCount"] > 0) {
                return true;
             }


            // Insert 50 rows in table using a transaction
            $insertProductDataSQL = "INSERT INTO products 
                (productEANCodes,
                productName,
                productManufacturer,
                productCategory,
                productPrice)  
                VALUES 
                (:EANCodes,
                :productName,
                :productManufacturer,
                :productCategory,
                :productPrice);";

            $this->db->beginTransaction();
            $statement = $this->db->prepare($insertProductDataSQL);
            $statement->bindParam(":EANCodes", $EANCode);
            $statement->bindParam(":productName", $productName);
            $statement->bindParam(":productManufacturer", $productManufacturer);
            $statement->bindParam(":productCategory", $productCategory);
            $statement->bindParam(":productPrice", $price);
            for ($i=0; $i<50; $i++) {
                $EANCode = random_int(1000000000000, 9999999999999);
                $productName = $productNames[$i];
                $productManufacturer = $productManufacturers[$i];
                $productCategory = $productCategories[$i];
                $price = random_int(1, 550) + (random_int(10, 99)/100);
                
                $statement->execute();
            }

            $this->db->commit();

            return true;
        } catch (PDOException $ex) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // $ex->errorInfo[1] Driver specific error code
            // $ex->errorInfo[2] Driver specific error message
            error_log($ex->errorInfo[1].";".$ex->errorInfo[2]);

            return false;
        }
    }
}

?>
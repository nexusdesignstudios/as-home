const fs = require('fs');

async function fetchHotelData() {
    const slug = 'malorca-hotel';
    // Use the correct endpoint and parameter name based on api.js and api.php
    const url = `http://127.0.0.1:8000/api/get_property?slug_id=${slug}&language_code=en`;

    console.log(`Fetching from: ${url}`);

    try {
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log("Response received!");
        
        // Log the structure
        if (data && data.data && Array.isArray(data.data) && data.data.length > 0) {
            const property = data.data[0];
            console.log("Property Name:", property.title);
            console.log("Hotel Rooms Count:", property.hotel_rooms ? property.hotel_rooms.length : 0);
            
            if (property.hotel_rooms && property.hotel_rooms.length > 0) {
                property.hotel_rooms.forEach((room, index) => {
                    console.log(`Room ${index + 1}: ${room.custom_room_type || 'Unknown'}`);
                    console.log(`  - ID: ${room.id}`);
                    console.log(`  - Base Guests: ${room.base_guests}`);
                    console.log(`  - Guest Pricing Rules:`, JSON.stringify(room.guest_pricing_rules));
                });
            }
        } else {
            console.log("No property data found or unexpected structure:", Object.keys(data));
            if (data.data) console.log("data.data type:", typeof data.data);
        }

        // Save to file for inspection
        fs.writeFileSync('hotel_data_response.json', JSON.stringify(data, null, 2));
        console.log("Saved full response to hotel_data_response.json");

    } catch (error) {
        console.error("Fetch failed:", error.message);
        if (error.cause) console.error("Cause:", error.cause);
    }
}

fetchHotelData();

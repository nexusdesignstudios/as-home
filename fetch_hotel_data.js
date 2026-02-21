
// const fetch = require('node-fetch'); // Use built-in fetch in Node 18+ // Use require if CommonJS, or native fetch if ESM
// For Node 18+, native fetch is available globally. If not, use node-fetch.
// Since we are running in a potentially older env or standard Node setup, try using global fetch first.

async function fetchHotelData() {
    const slug = 'malorca-hotel';
    const url = `http://127.0.0.1:8000/api/get_property?slug_id=${slug}`;

    console.log(`Fetching data from: ${url}`);

    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        console.log('Response Status:', response.status);
        
        if (data.error) {
            console.error('API Error:', data.message);
            return;
        }

        const property = data.data && data.data[0];
        if (!property) {
            console.error('No property data found');
            return;
        }

        console.log('Property Found:', property.title);
        console.log('Hotel Rooms Count:', property.hotel_rooms ? property.hotel_rooms.length : 0);

        if (property.hotel_rooms) {
            property.hotel_rooms.forEach((room, index) => {
                console.log(`\nRoom ${index + 1}:`);
                console.log(`  ID: ${room.id}`);
                console.log(`  Base Guests: ${room.base_guests}`);
                console.log(`  Min/Max Guests: ${room.min_guests}/${room.max_guests}`);
                console.log(`  Guest Pricing Rules:`, JSON.stringify(room.guest_pricing_rules));
            });
        }
    } catch (error) {
        console.error('Fetch Error:', error.message);
    }
}

fetchHotelData();

// JavaScript code to run in browser console to debug the issue
// This will help us see what's happening with the reservations

console.log("=== DEBUGGING GREEN HOTEL 2 RESERVATIONS ===");

// Check if the component is loaded
if (typeof window !== 'undefined') {
    
    // Look for reservation data in the Redux store or component state
    console.log("1. Checking for reservation data...");
    
    // Try to access the Redux store if available
    if (window.store && window.store.getState) {
        const state = window.store.getState();
        console.log("Redux store state:", state);
        
        // Look for reservations in different possible locations
        if (state.User_signup && state.User_signup.data) {
            console.log("User data:", state.User_signup.data);
        }
    }
    
    // Check if there are any global variables with reservation data
    console.log("2. Checking global variables...");
    console.log("Window object keys:", Object.keys(window).filter(key => key.includes('reservation') || key.includes('booking')));
    
    // Look for reservation elements in the DOM
    console.log("3. Checking DOM for reservation elements...");
    const reservationElements = document.querySelectorAll('[data-reservation-id], .reservation-row, .booking-row');
    console.log("Found reservation elements:", reservationElements.length);
    
    reservationElements.forEach((element, index) => {
        console.log(`Reservation ${index + 1}:`);
        console.log("- Element:", element);
        console.log("- Text content:", element.textContent);
        console.log("- Dataset:", element.dataset);
        
        // Look for status indicators
        const statusElement = element.querySelector('.status, .badge, [class*="status"]');
        if (statusElement) {
            console.log("- Status element:", statusElement.textContent);
        }
        
        // Look for ID
        const idElement = element.querySelector('[class*="id"], .reservation-id');
        if (idElement) {
            console.log("- ID element:", idElement.textContent);
        }
    });
    
    // Check calendar component
    console.log("4. Checking calendar component...");
    const calendarElements = document.querySelectorAll('[class*="calendar"], [class*="date"]');
    console.log("Found calendar elements:", calendarElements.length);
    
    // Look for specific reservation IDs mentioned
    const targetIds = [896, 897, 898];
    targetIds.forEach(id => {
        const element = document.querySelector(`[data-reservation-id="${id}"], *:contains("#${id}")`);
        if (element) {
            console.log(`✅ Found reservation #${id} in DOM`);
            console.log("- Element:", element);
            console.log("- Parent:", element.parentElement);
        } else {
            console.log(`❌ Reservation #${id} not found in DOM`);
        }
    });
    
    // Check for any error messages or loading states
    console.log("5. Checking for errors/loading states...");
    const errorElements = document.querySelectorAll('.error, .alert-error, [class*="error"]');
    const loadingElements = document.querySelectorAll('.loading, .spinner, [class*="loading"]');
    
    console.log("Error elements:", errorElements.length);
    console.log("Loading elements:", loadingElements.length);
    
    console.log("=== END DEBUGGING ===");
    
    // Additional function to monitor network requests
    console.log("6. Monitoring network requests...");
    if (typeof fetch !== 'undefined') {
        const originalFetch = fetch;
        fetch = function(...args) {
            console.log("Fetch request:", args[0]);
            return originalFetch.apply(this, args);
        };
    }
    
    // Monitor XHR requests
    if (typeof XMLHttpRequest !== 'undefined') {
        const originalOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            if (url.includes('reservation') || url.includes('booking')) {
                console.log("XHR request:", method, url);
            }
            return originalOpen.apply(this, [method, url, ...args]);
        };
    }
    
} else {
    console.log("Not in browser environment");
}
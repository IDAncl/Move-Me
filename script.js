// Mock driver data generator
const firstNames = ['John', 'Mike', 'Sarah', 'David', 'Emma', 'Chris', 'Lisa', 'Robert', 'Jennifer', 'Alex'];
const lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
const vehicleTypes = ['van', 'truck', 'pickup', 'van', 'truck'];

const generateDriver = (index, objectType, distance) => {
    const firstName = firstNames[Math.floor(Math.random() * firstNames.length)];
    const lastName = lastNames[Math.floor(Math.random() * lastNames.length)];
    const vehicle = vehicleTypes[Math.floor(Math.random() * vehicleTypes.length)];
    const rating = (4 + Math.random()).toFixed(1);
    const reviews = Math.floor(Math.random() * 200) + 20;
    
    // Base price calculation
    let basePrice = 50;
    if (objectType === 'piano' || objectType === 'appliances') basePrice += 40;
    if (objectType === 'furniture') basePrice += 25;
    if (vehicle === 'truck') basePrice += 30;
    
    const price = basePrice + Math.floor(Math.random() * 50) + (distance * 2);
    
    return {
        id: index,
        name: `${firstName} ${lastName}`,
        rating: parseFloat(rating),
        reviews: reviews,
        vehicle: vehicle,
        vehicleName: vehicle.charAt(0).toUpperCase() + vehicle.slice(1),
        price: price,
        image: `https://static.photos/people/200x200/${index + 20}`,
        availability: Math.random() > 0.3 ? 'Available' : 'Busy',
        eta: Math.floor(Math.random() * 30) + 5,
        completedMoves: Math.floor(Math.random() * 500) + 50
    };
};

let currentDrivers = [];
let selectedDriver = null;

document.getElementById('bookingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Get form values
    const pickup = document.getElementById('pickup').value;
    const delivery = document.getElementById('delivery').value;
    const date = document.getElementById('moveDate').value;
    const objectType = document.getElementById('objectType').value;
    
    // Show loading
    document.getElementById('loadingSection').classList.remove('hidden');
    document.getElementById('resultsSection').classList.add('hidden');
    
    // Scroll to loading
    document.getElementById('loadingSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Simulate API call delay
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Generate mock drivers (simulate distance as random 5-25 miles)
    const distance = Math.floor(Math.random() * 20) + 5;
    currentDrivers = Array.from({ length: 6 }, (_, i) => generateDriver(i, objectType, distance));
    
    // Hide loading, show results
    document.getElementById('loadingSection').classList.add('hidden');
    document.getElementById('resultsSection').classList.remove('hidden');
    
    // Render drivers
    renderDrivers(currentDrivers);
    
    // Scroll to results
    document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Refresh icons
    lucide.createIcons();
});

function renderDrivers(drivers) {
    const grid = document.getElementById('driversGrid');
    document.getElementById('offerCount').textContent = drivers.length;
    
    grid.innerHTML = drivers.map(driver => `
        <div class="driver-card bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden fade-in" data-vehicle="${driver.vehicle}">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <img src="${driver.image}" alt="${driver.name}" class="w-14 h-14 rounded-full object-cover border-2 border-indigo-100">
                        <div>
                            <h3 class="font-bold text-gray-900">${driver.name}</h3>
                            <div class="flex items-center space-x-1 text-sm">
                                <i data-lucide="star" class="w-4 h-4 text-yellow-400 fill-current"></i>
                                <span class="font-semibold text-gray-700">${driver.rating}</span>
                                <span class="text-gray-500">(${driver.reviews} reviews)</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-indigo-600">$${driver.price}</p>
                        <p class="text-xs text-gray-500">estimated</p>
                    </div>
                </div>
                
                <div class="space-y-3 mb-4">
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <i data-lucide="truck" class="w-4 h-4"></i>
                        <span class="font-medium">${driver.vehicleName}</span>
                        <span class="text-gray-400">•</span>
                        <span>${driver.completedMoves} completed moves</span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        <span>Can pickup in ${driver.eta} mins</span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm">
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${driver.availability === 'Available' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
                            ${driver.availability}
                        </span>
                    </div>
                </div>
                
                <button onclick="openBookingModal(${driver.id})" 
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition flex items-center justify-center space-x-2 ${driver.availability !== 'Available' ? 'opacity-50 cursor-not-allowed' : ''}"
                    ${driver.availability !== 'Available' ? 'disabled' : ''}>
                    <span>Accept Offer</span>
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

// Filter functionality
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        // Update active state
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.classList.remove('bg-indigo-600', 'text-white');
            b.classList.add('bg-white', 'text-gray-700', 'border', 'border-gray-300');
        });
        e.target.classList.remove('bg-white', 'text-gray-700', 'border', 'border-gray-300');
        e.target.classList.add('bg-indigo-600', 'text-white');
        
        const filter = e.target.dataset.filter;
        const filtered = filter === 'all' ? currentDrivers : currentDrivers.filter(d => d.vehicle === filter);
        renderDrivers(filtered);
    });
});

// Sort functionality
document.getElementById('sortBy').addEventListener('change', (e) => {
    const sortType = e.target.value;
    let sorted = [...currentDrivers];
    
    switch(sortType) {
        case 'price-low':
            sorted.sort((a, b) => a.price - b.price);
            break;
        case 'price-high':
            sorted.sort((a, b) => b.price - a.price);
            break;
        case 'rating':
            sorted.sort((a, b) => b.rating - a.rating);
            break;
    }
    
    renderDrivers(sorted);
});

function openBookingModal(driverId) {
    selectedDriver = currentDrivers.find(d => d.id === driverId);
    if (!selectedDriver) return;
    
    const modal = document.getElementById('bookingModal');
    const content = document.getElementById('modalContent');
    
    content.innerHTML = `
        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
            <img src="${selectedDriver.image}" class="w-16 h-16 rounded-full object-cover">
            <div>
                <h4 class="font-bold text-lg">${selectedDriver.name}</h4>
                <div class="flex items-center space-x-1 text-sm text-gray-600">
                    <i data-lucide="star" class="w-4 h-4 text-yellow-400 fill-current"></i>
                    <span>${selectedDriver.rating} • ${selectedDriver.vehicleName}</span>
                </div>
            </div>
            <div class="ml-auto text-right">
                <p class="text-2xl font-bold text-indigo-600">$${selectedDriver.price}</p>
            </div>
        </div>
        
        <div class="space-y-3 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Service Fee</span>
                <span class="font-medium">$${Math.floor(selectedDriver.price * 0.1)}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Driver Payment</span>
                <span class="font-medium">$${Math.floor(selectedDriver.price * 0.9)}</span>
            </div>
            <div class="flex justify-between py-2 text-lg font-bold">
                <span>Total</span>
                <span class="text-indigo-600">$${selectedDriver.price}</span>
            </div>
        </div>
        
        <div class="bg-blue-50 p-4 rounded-lg flex items-start space-x-3">
            <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5"></i>
            <p class="text-sm text-blue-700">The driver will contact you within 10 minutes to confirm pickup details.</p>
        </div>
    `;
    
    modal.classList.remove('hidden');
    lucide.createIcons();
}

function closeModal() {
    document.getElementById('bookingModal').classList.add('hidden');
    selectedDriver = null;
}

function confirmBooking() {
    closeModal();
    
    // Show success message
    const success = document.getElementById('successMessage');
    success.classList.remove('hidden');
    
    // Hide after 5 seconds
    setTimeout(() => {
        success.classList.add('hidden');
    }, 5000);
    
    // Reset form
    document.getElementById('bookingForm').reset();
    document.getElementById('resultsSection').classList.add('hidden');
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Close modal on outside click
document.getElementById('bookingModal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeModal();
});
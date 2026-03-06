const form = document.getElementById('searchForm');
const resultsDiv = document.getElementById('results');
const loading = document.getElementById('loading');
const cacheResult = document.getElementById('cache-result');
const warningDiv = document.getElementById('warning');

function escapeHTML(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/[&<>"']/g, function(match) {
        return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[match];
    });
}

function createStars(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating - fullStars >= 0.5;
    let starsHTML = '';
    for(let i=0; i<fullStars; i++) starsHTML += '★';
    if(halfStar) starsHTML += '½';
    for(let i=fullStars + (halfStar?1:0); i<5; i++) starsHTML += '☆';
    return starsHTML;
}

function createHotelCard(hotel) {
    const card = document.createElement('div');
    card.className = 'hotel-card';

    // Image
    const img = document.createElement('img');
    img.src = hotel.image || 'https://picsum.photos/seed/picsum/500';
    img.alt = escapeHTML(hotel.name || 'Hotel Image');
    card.appendChild(img);

    // Price badge
    const priceBadge = document.createElement('div');
    priceBadge.className = 'price-badge';
    priceBadge.textContent = '$' + (hotel.price !== undefined ? hotel.price : '0');
    card.appendChild(priceBadge);

    // Content
    const content = document.createElement('div');
    content.className = 'content';

    const name = document.createElement('h3');
    name.textContent = escapeHTML(hotel.name || 'Unknown');
    content.appendChild(name);

    const city = document.createElement('p');
    city.textContent = 'City: ' + escapeHTML(hotel.city || 'Unknown');
    content.appendChild(city);

    const rating = document.createElement('p');
    rating.className = 'stars';
    rating.textContent = createStars(hotel.rating || 0);
    content.appendChild(rating);

    card.appendChild(content);

    return card;
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Clear previous results and messages
    resultsDiv.innerHTML = '';
    cacheResult.innerHTML = '';       // ✅ Clear cached badge
    warningDiv.style.display = 'none';
    
    loading.style.display = 'block';

    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    try {
        const res = await fetch(`/api/hotels/search?${params.toString()}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        });

        loading.style.display = 'none';

        if (res.status === 429) {
            warningDiv.textContent = "You are making requests too quickly. Please wait a moment.";
            warningDiv.style.display = 'block';
            return;
        }

        const data = await res.json();

        if (!data || !Array.isArray(data.results) || data.results.length === 0) {
            warningDiv.textContent = 'No hotels found.';
            warningDiv.style.display = 'block';
            return;
        }

        // Show cached badge if available
        if (data.cached) {
            const badge = document.createElement('div');
            badge.textContent = 'Cached Results';
            badge.className = 'cached-badge';
            cacheResult.appendChild(badge);
        }

        // Render hotel cards
        data.results.forEach(hotel => {
            const card = createHotelCard(hotel);
            resultsDiv.appendChild(card);
        });

    } catch(err) {
        console.error(err);
        loading.style.display = 'none';
        warningDiv.textContent = "Error fetching hotels.";
        warningDiv.style.display = 'block';
    }
});
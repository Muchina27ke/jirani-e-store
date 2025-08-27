// Lazy loading for images
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));
});

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Optimize search input
const searchInput = document.querySelector('.search-input');
if (searchInput) {
    const debouncedSearch = debounce(async function(e) {
        const query = e.target.value.trim();
        if (query.length < 2) return;

        try {
            const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            updateSearchResults(data);
        } catch (error) {
            console.error('Search error:', error);
        }
    }, 300);

    searchInput.addEventListener('input', debouncedSearch);
}

// Cache management
const cache = {
    data: new Map(),
    maxAge: 5 * 60 * 1000, // 5 minutes

    set(key, value) {
        this.data.set(key, {
            value,
            timestamp: Date.now()
        });
    },

    get(key) {
        const item = this.data.get(key);
        if (!item) return null;

        if (Date.now() - item.timestamp > this.maxAge) {
            this.data.delete(key);
            return null;
        }

        return item.value;
    }
};

// Optimize API calls
async function fetchWithCache(url, options = {}) {
    const cacheKey = url + JSON.stringify(options);
    const cachedData = cache.get(cacheKey);

    if (cachedData) {
        return cachedData;
    }

    try {
        const response = await fetch(url, options);
        const data = await response.json();
        cache.set(cacheKey, data);
        return data;
    } catch (error) {
        console.error('API call error:', error);
        throw error;
    }
}

// Update search results
function updateSearchResults(data) {
    const resultsContainer = document.querySelector('.search-results');
    if (!resultsContainer) return;

    if (!data.length) {
        resultsContainer.innerHTML = '<div class="no-results">No results found</div>';
        return;
    }

    resultsContainer.innerHTML = data.map(item => `
        <div class="search-result-item">
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" 
                 data-src="${item.image_url}" 
                 alt="${item.name}"
                 class="lazy">
            <div class="item-details">
                <h4>${item.name}</h4>
                <p>${item.description}</p>
                <span class="price">KSh ${item.price}</span>
            </div>
        </div>
    `).join('');

    // Initialize lazy loading for new images
    const newLazyImages = resultsContainer.querySelectorAll('img[data-src]');
    newLazyImages.forEach(img => imageObserver.observe(img));
}

// Performance monitoring
const performanceMetrics = {
    timings: {},
    
    start(label) {
        this.timings[label] = performance.now();
    },
    
    end(label) {
        if (!this.timings[label]) return;
        
        const duration = performance.now() - this.timings[label];
        console.log(`${label}: ${duration.toFixed(2)}ms`);
        
        // Send to analytics if needed
        if (window.analytics) {
            window.analytics.track('Performance', {
                label,
                duration
            });
        }
    }
};

// Initialize performance monitoring for page load
performanceMetrics.start('pageLoad');
window.addEventListener('load', () => {
    performanceMetrics.end('pageLoad');
}); 
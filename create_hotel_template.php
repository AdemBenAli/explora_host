<?php
$content = <<<'TWIG'
{% extends 'base.html.twig' %}

{% block title %}Hotels | Explora{% endblock %}

{% block stylesheets %}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.7/build/pannellum.css">
{% endblock %}

{% block body %}
<div class="front-hotels-shell">

    <!-- NAVBAR HORIZONTALE -->
    <header class="front-navbar">
        <div class="front-navbar-inner">
            <!-- LOGO GAUCHE 240px -->
            <a href="{{ path('app_hebergement_front') }}" class="front-brand">
                <img src="{{ asset('images/Explora.png') }}" alt="Explora" class="front-brand-logo">
            </a>

            <!-- BOUTONS CENTRE -->
            <nav class="front-center-nav">
                <a href="#" class="front-nav-pill">Home</a>
                <a href="#" class="front-nav-pill">Voyages</a>
                <a href="#" class="front-nav-pill">Transport</a>
                <a href="{{ path('app_hebergement_front') }}" class="front-nav-pill front-nav-pill-active">Hotels</a>
            </nav>

            <!-- BOUTONS DROITE -->
            <div class="front-right-nav">
                <a href="{{ path('app_hebergement_index') }}" class="front-outline-pill">Dashboard</a>
                <a href="{{ path('app_reservation_history') }}" class="front-outline-pill">History</a>
                <a href="#" class="front-accent-pill">Profile</a>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="front-hero-hotels">
        <div class="front-hero-content">
            <h1>Find Your Perfect Stay</h1>
            <p>From cozy boutique hotels to luxury resorts</p>
        </div>
    </section>

    <!-- SEARCH BAR -->
    <section class="front-search-card-wrap">
        <div class="front-search-card">
            <form method="get" action="{{ path('app_hebergement_front') }}" class="front-search-form" autocomplete="off">
                <div class="front-search-main">
                    <div class="front-search-field front-search-field-large">
                        <label for="destination">Destination (Country)</label>
                        <input
                            type="text"
                            id="destination"
                            name="destination"
                            value="{{ filters.destination }}"
                            placeholder="Where are you going?"
                            autocomplete="off"
                        >
                    </div>

                    <div class="front-search-field">
                        <label for="type">Accommodation Type</label>
                        <select id="type" name="type">
                            <option value="">Select type</option>
                            <option value="hotel" {{ filters.type == 'hotel' ? 'selected' : '' }}>Hotel</option>
                            <option value="hostel" {{ filters.type == 'hostel' ? 'selected' : '' }}>Hostel</option>
                            <option value="motel" {{ filters.type == 'motel' ? 'selected' : '' }}>Motel</option>
                            <option value="maison" {{ filters.type == 'maison' ? 'selected' : '' }}>Maison</option>
                            <option value="appartement" {{ filters.type == 'appartement' ? 'selected' : '' }}>Appartement</option>
                        </select>
                    </div>

                    <div class="front-search-actions">
                        <button type="submit" class="front-btn-primary">Search</button>
                        <a href="{{ path('app_hebergement_front') }}" class="front-btn-outline">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- MAIN LAYOUT: FILTERS + HOTELS -->
    <section class="front-hotels-page">

        <!-- LEFT SIDEBAR: FILTERS -->
        <aside class="front-filters-card">
            <h2>Filters</h2>
            <div class="front-divider"></div>

            <form method="get" action="{{ path('app_hebergement_front') }}" class="front-filters-form">
                <input type="hidden" name="destination" value="{{ filters.destination }}">
                <input type="hidden" name="type" value="{{ filters.type }}">

                <div class="front-filter-group">
                    <label for="minPrice">Min price</label>
                    <input type="number" id="minPrice" name="minPrice" value="{{ filters.minPrice }}">
                </div>

                <div class="front-filter-group">
                    <label for="maxPrice">Max price</label>
                    <input type="number" id="maxPrice" name="maxPrice" value="{{ filters.maxPrice }}">
                </div>

                <div class="front-filter-group">
                    <label for="sort">Select order</label>
                    <select id="sort" name="sort">
                        <option value="price_asc" {{ filters.sort == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ filters.sort == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="name_asc" {{ filters.sort == 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                        <option value="name_desc" {{ filters.sort == 'name_desc' ? 'selected' : '' }}>Name: Z to A</option>
                    </select>
                </div>

                <label class="front-checkbox-line">
                    <input type="checkbox" name="specialCouple" value="1" {{ filters.specialCouple ? 'checked' : '' }}>
                    <span>Special Couple</span>
                </label>

                <label class="front-checkbox-line">
                    <input type="checkbox" name="under18Allowed" value="1" {{ filters.under18Allowed ? 'checked' : '' }}>
                    <span>-18 years allowed</span>
                </label>

                <label class="front-checkbox-line">
                    <input type="checkbox" name="seaView" value="1" {{ filters.seaView ? 'checked' : '' }}>
                    <span>Sea view</span>
                </label>

                <button type="submit" class="front-btn-primary front-filter-submit">
                    Apply Filters
                </button>
            </form>
        </aside>

        <!-- RIGHT SIDE: HOTELS -->
        <div class="front-hotels-results">

            {% for label, messages in app.flashes %}
                {% for message in messages %}
                    <div
                        style="
                            margin-bottom: 18px;
                            padding: 14px 16px;
                            border-radius: 14px;
                            font-weight: 700;
                            background: {{ label == 'success' ? '#e9f8ee' : '#fdeceb' }};
                            color: {{ label == 'success' ? '#1f7a39' : '#b42318' }};
                            border: 1px solid {{ label == 'success' ? '#b7e4c7' : '#f5c2c7' }};
                        "
                    >
                        {{ message }}
                    </div>
                {% endfor %}
            {% endfor %}

            <h2>Hotels found <span>({{ hotelsCount }})</span></h2>

            <div class="front-hotels-list">
                {% for h in hebergements %}
                    <article class="front-hotel-card" data-hotel-id="{{ h.id }}">
                        <!-- IMAGE GAUCHE -->
                        <div class="front-hotel-image-wrap">
                            <img src="{{ h.image ? (h.image starts with 'http' ? h.image : asset('uploads/hebergements/' ~ h.image)) : 'https://images.unsplash.com/photo-1566073771259-6a8506099945' }}" alt="{{ h.nom }}" class="front-hotel-image">
                        </div>

                        <!-- CONTENU DROITE -->
                        <div class="front-hotel-content">
                            <div class="front-hotel-main">
                                <h3>{{ h.nom|lower }}</h3>

                                <div class="front-hotel-location">
                                    {{ h.localisation ?: 'Unknown location' }}
                                    {% if h.pays %}/ {{ h.pays }}{% endif %}
                                </div>

                                <div class="front-hotel-description">
                                    {{ h.description ?: 'A comfortable stay.' }}
                                </div>

                                <div class="front-hotel-price">
                                    ${{ h.prixParNuit is not null ? h.prixParNuit|number_format(1, '.', '') : '0.0' }}
                                </div>

                                <div class="front-hotel-price-sub">per night</div>

                                <div class="front-hotel-stars-line">
                                    <div class="front-stars">
                                        {% for i in 1..5 %}
                                            <span class="front-star-{{ (i|round) <= 3 ? 'filled' : 'empty' }}">★</span>
                                        {% endfor %}
                                    </div>
                                    <span class="front-review-text">5.0 / 10 • 0 reviews</span>
                                </div>

                                <div class="front-hotel-tags">
                                    {% if h.specialCouple %}
                                        <span class="front-tag">Special Couple</span>
                                    {% endif %}
                                    {% if h.under18Allowed %}
                                        <span class="front-tag">-18 allowed</span>
                                    {% endif %}
                                    {% if h.seaView %}
                                        <span class="front-tag">Sea view</span>
                                    {% endif %}
                                    {% if h.capacite %}
                                        <span class="front-tag">Capacity: {{ h.capacite }}</span>
                                    {% endif %}
                                </div>
                            </div>

                            <div class="front-hotel-actions">
                                <button type="button" class="front-btn-outline action-pill">Add a review</button>
                                <button type="button" class="front-btn-outline action-pill">View reviews</button>
                                <button type="button" class="front-btn-outline action-pill front-btn-map">Map</button>
                                <button type="button" class="front-btn-primary action-pill">Book Now</button>
                            </div>
                        </div>
                    </article>
                {% else %}
                    <div class="front-empty-state">
                        No hotels match your filters.
                    </div>
                {% endfor %}
            </div>

            {% if pagination is defined and pagination.pageCount > 1 %}
                <div class="front-pagination-wrapper">
                    {{ knp_pagination_render(pagination) }}
                </div>
            {% endif %}
        </div>

    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.7/build/pannellum.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.4.0/axios.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Autocomplete destination
        const destinationInput = document.getElementById('destination');
        if (destinationInput) {
            destinationInput.addEventListener('input', function() {
                // Placeholder for country autocomplete
            });
        }

        // Book Now button
        document.querySelectorAll('.front-btn-primary.action-pill').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.front-hotel-card');
                const hotelId = card.dataset.hotelId;
                // Open booking modal
            });
        });

        // Map button
        document.querySelectorAll('.front-btn-map').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.front-hotel-card');
                // Open map modal
            });
        });
    });
</script>
{% endblock %}
TWIG;

file_put_contents('c:\Users\DELL\Explora_Web\templates\hotels\index.html.twig', $content, LOCK_EX);
echo "File created successfully\n";

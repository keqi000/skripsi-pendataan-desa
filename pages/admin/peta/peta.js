// Peta Admin JavaScript
class PetaAdminComponent {
    constructor() {
        this.map = null;
        this.markers = [];
        this.layers = {};
        this.layerGroups = {
            fasilitas: null,
            jalan: null,
            jembatan: null,
            desa: null,
            kecamatan: null,
            kabupaten: null,
            provinsi: null
        };
        this.tibawaFilter = false;
        this.allBoundaries = [];
        this.currentPage = { fasilitas: 1, jalan: 1, jembatan: 1 };
        this.itemsPerPage = 10;
        this.filteredData = { fasilitas: [], jalan: [], jembatan: [] };
        
        // Tibawa coordinates (approximate center)
        this.tibawaCenter = {
            lat: 0.5547, // Approximate latitude for Tibawa
            lng: 123.0581 // Approximate longitude for Tibawa
        };
        
        this.init();
    }
    
    init() {
        console.log('🗺️ Initializing Peta Admin Component');
        this.bindEvents();
        this.initMap();
    }
    
    initMap() {
        // Wait for Leaflet to be available
        if (typeof L === 'undefined') {
            console.log('Waiting for Leaflet...');
            setTimeout(() => this.initMap(), 100);
            return;
        }
        
        console.log('Leaflet available, initializing map...');
        
        // Initialize Leaflet map
        this.map = L.map('mapContainer').setView([this.tibawaCenter.lat, this.tibawaCenter.lng], 12);
        
        // Initialize layer groups
        Object.keys(this.layerGroups).forEach(key => {
            this.layerGroups[key] = L.layerGroup().addTo(this.map);
        });
        
        // Add CartoDB Positron tiles (minimal labels)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap © CartoDB'
        }).addTo(this.map);
        
        // Add custom controls
        this.addMapControls();
        
        // Load data after map is ready
        this.loadData();
        
        // Remove loading indicator
        setTimeout(() => {
            const loading = document.querySelector('.map-loading');
            if (loading) {
                loading.style.display = 'none';
            }
        }, 1000);
    }
    
    loadLeaflet() {
        // Load Leaflet CSS
        const leafletCSS = document.createElement('link');
        leafletCSS.rel = 'stylesheet';
        leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(leafletCSS);
        
        // Load Leaflet JS
        const leafletJS = document.createElement('script');
        leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        leafletJS.onload = () => {
            console.log('Leaflet loaded, initializing map...');
            setTimeout(() => {
                this.initMapAfterLoad();
            }, 100);
        };
        document.head.appendChild(leafletJS);
    }
    
    initMapAfterLoad() {
        if (typeof L === 'undefined') {
            console.log('Leaflet still not ready, retrying...');
            setTimeout(() => this.initMapAfterLoad(), 100);
            return;
        }
        
        // Initialize Leaflet map
        this.map = L.map('mapContainer').setView([this.tibawaCenter.lat, this.tibawaCenter.lng], 12);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);
        
        // Add custom controls
        this.addMapControls();
        
        // Load data after map is ready
        this.loadData();
        
        // Remove loading indicator
        setTimeout(() => {
            const loading = document.querySelector('.map-loading');
            if (loading) {
                loading.style.display = 'none';
            }
        }, 1000);
    }
    
    addMapControls() {
        // Add custom control container
        const controlsDiv = document.createElement('div');
        controlsDiv.className = 'map-controls';
        controlsDiv.innerHTML = `
            <button class="map-control-btn" onclick="resetMapView()" title="Reset View">
                <i class="fas fa-home"></i>
            </button>
            <button class="map-control-btn" id="tibawaFilterBtn" onclick="toggleTibawaFilter()" title="Filter Tibawa">
                <i class="fas fa-filter"></i>
            </button>
            <button class="map-control-btn" onclick="toggleFullscreen()" title="Fullscreen">
                <i class="fas fa-expand"></i>
            </button>
        `;
        
        document.querySelector('.map-container').appendChild(controlsDiv);
    }
    
    loadData() {
        if (!window.petaData) {
            console.error('❌ Peta data not available');
            return;
        }
        
        console.log('📊 Loading peta data:', window.petaData);
        
        // Load village boundaries (simplified polygons)
        this.loadVillageBoundaries();
        
        // Load facilities
        this.loadFacilities();
        
        // Load roads
        this.loadRoads();
        
        // Load bridges
        this.loadBridges();
    }
    
    loadVillageBoundaries() {
        console.log('Loading village boundaries...');
        // Load administrative boundaries from database
        this.loadAdministrativeBoundaries();
        
        // Create village markers only if they have coordinates
        window.petaData.desa.forEach((desa, index) => {
            const lat = parseFloat(desa.koordinat_latitude);
            const lng = parseFloat(desa.koordinat_longitude);
            
            // Only add marker if coordinates exist
            if (lat && lng) {
                console.log(`Adding village marker for ${desa.nama_desa} at [${lat}, ${lng}]`);
                
                const marker = L.circleMarker([lat, lng], {
                    radius: 10,
                    fillColor: '#1abc9c',
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(this.layerGroups.desa);
                
                marker.bindPopup(`
                    <div class="popup-content">
                        <h5>${desa.nama_desa}</h5>
                        <p><strong>Luas:</strong> ${desa.luas_wilayah || 'N/A'} ha</p>
                        <p><strong>Dusun:</strong> ${desa.jumlah_dusun || 0}</p>
                        <p><strong>RT/RW:</strong> ${desa.jumlah_rt || 0}/${desa.jumlah_rw || 0}</p>
                    </div>
                `);
                
                this.markers.push({
                    type: 'desa',
                    id: desa.id_desa,
                    marker: marker
                });
            }
        });
    }
    
    async loadAdministrativeBoundaries() {
        try {
            const response = await fetch(window.BASE_URL + 'api/administrative-boundaries.php');
            const boundaries = await response.json();
            this.allBoundaries = boundaries;
            
            this.renderBoundaries();
        } catch (error) {
            console.error('Error loading administrative boundaries:', error);
        }
    }
    
    renderBoundaries() {
        // Clear existing boundaries
        this.layerGroups.desa.clearLayers();
        this.layerGroups.kecamatan.clearLayers();
        this.layerGroups.kabupaten.clearLayers();
        this.layerGroups.provinsi.clearLayers();
        
        let boundariesToShow = this.allBoundaries;
        
        if (this.tibawaFilter) {
            // Filter only Tibawa related boundaries (kd_kecamatan = "004")
            boundariesToShow = this.allBoundaries.filter(boundary => {
                if (boundary.boundary_type === 'kecamatan' || boundary.boundary_type === 'desa') {
                    return boundary.kd_kecamatan === '004';
                }
                return boundary.boundary_type === 'kabupaten' || boundary.boundary_type === 'provinsi';
            });
        }
        
        boundariesToShow.forEach(boundary => {
            if (this.tibawaFilter && (boundary.boundary_type === 'kabupaten' || boundary.boundary_type === 'provinsi')) {
                return; // Skip kabupaten and provinsi when filter is on
            }
                try {
                    const geojson = JSON.parse(boundary.geojson_data);
                    const color = this.getBoundaryColor(boundary.boundary_type);
                    
                    const layer = L.geoJSON(geojson, {
                        style: {
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.2,
                            weight: boundary.boundary_type === 'desa' ? 2 : 3
                        }
                    }).addTo(this.layerGroups[boundary.boundary_type]).bindPopup(`
                        <div class="popup-content">
                            <h5>${boundary.nm_desa || boundary.nm_kelurahan || boundary.nm_kecamatan || 'Boundary'}</h5>
                            <p><strong>Tipe:</strong> ${boundary.boundary_type}</p>
                        </div>
                    `);
                    
                    // Add labels for boundaries
                    if (boundary.boundary_type === 'kecamatan' && boundary.nm_kecamatan) {
                        const bounds = layer.getBounds();
                        const center = bounds.getCenter();
                        
                        L.marker(center, {
                            icon: L.divIcon({
                                className: 'kecamatan-label',
                                html: `<div class="label-text">${boundary.nm_kecamatan}</div>`,
                                iconSize: [100, 20],
                                iconAnchor: [50, 10]
                            })
                        }).addTo(this.layerGroups.kecamatan);
                    }
                    
                    if (boundary.boundary_type === 'kabupaten' && boundary.nm_kabupaten) {
                        const bounds = layer.getBounds();
                        const center = bounds.getCenter();
                        
                        L.marker(center, {
                            icon: L.divIcon({
                                className: 'kabupaten-label',
                                html: `<div class="label-text-kabupaten">${boundary.nm_kabupaten}</div>`,
                                iconSize: [120, 24],
                                iconAnchor: [60, 12]
                            })
                        }).addTo(this.layerGroups.kabupaten);
                    }
                    
                    if (boundary.boundary_type === 'desa' && (boundary.nm_desa || boundary.nm_kelurahan)) {
                        const bounds = layer.getBounds();
                        const center = bounds.getCenter();
                        const name = boundary.nm_desa || boundary.nm_kelurahan;
                        
                        L.marker(center, {
                            icon: L.divIcon({
                                className: 'desa-label',
                                html: `<div class="label-text-desa">${name}</div>`,
                                iconSize: [80, 16],
                                iconAnchor: [40, 8]
                            })
                        }).addTo(this.layerGroups.desa);
                    }
                } catch (e) {
                    console.error('Error loading boundary:', boundary.nm_desa || boundary.nm_kelurahan || boundary.nm_kecamatan, e);
                }
            });
    }
    
    getBoundaryColor(type) {
        const colors = {
            'provinsi': '#8e44ad',
            'kabupaten': '#e67e22', 
            'kecamatan': '#2980b9',
            'desa': '#1abc9c'
        };
        return colors[type] || '#666';
    }
    
    createCircleBoundary(lat, lng) {
        return L.circle([lat, lng], {
            color: '#27ae60',
            fillColor: '#27ae60',
            fillOpacity: 0.2,
            weight: 3,
            radius: 1500 // 1.5km radius
        }).addTo(this.map);
    }
    
    loadFacilities() {
        console.log('Loading facilities...');
        window.petaData.fasilitas.forEach(fasilitas => {
            // Use coordinates from database or fallback to village center
            let lat = parseFloat(fasilitas.koordinat_latitude);
            let lng = parseFloat(fasilitas.koordinat_longitude);
            
            if (!lat || !lng) {
                const desa = window.petaData.desa.find(d => d.nama_desa === fasilitas.nama_desa);
                lat = parseFloat(desa?.koordinat_latitude) || this.tibawaCenter.lat;
                lng = parseFloat(desa?.koordinat_longitude) || this.tibawaCenter.lng;
                lat += (Math.random() - 0.5) * 0.01;
                lng += (Math.random() - 0.5) * 0.01;
            }
            
            console.log(`Adding facility ${fasilitas.nama_fasilitas} at [${lat}, ${lng}]`);
            
            const marker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: '#e74c3c',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(this.layerGroups.fasilitas);
            
            marker.bindPopup(`
                <div class="popup-content">
                    <h5>${fasilitas.nama_fasilitas}</h5>
                    <p><strong>Jenis:</strong> ${fasilitas.jenis_pendidikan}</p>
                    <p><strong>Desa:</strong> ${fasilitas.nama_desa}</p>
                    <p><strong>Kondisi:</strong> ${fasilitas.kondisi_bangunan}</p>
                    <p><strong>Kapasitas:</strong> ${fasilitas.kapasitas_siswa} siswa</p>
                    <p><strong>Guru:</strong> ${fasilitas.jumlah_guru} orang</p>
                </div>
            `);
            
            this.markers.push({
                type: 'fasilitas',
                id: fasilitas.id_fasilitas,
                marker: marker
            });
        });
    }
    
    loadRoads() {
        console.log('Loading roads...');
        window.petaData.jalan.forEach(jalan => {
            // Use center point coordinates
            let lat = parseFloat(jalan.koordinat_start_lat);
            let lng = parseFloat(jalan.koordinat_start_lng);
            
            if (!lat || !lng) {
                const desa = window.petaData.desa.find(d => d.nama_desa === jalan.nama_desa);
                lat = parseFloat(desa?.koordinat_latitude) || this.tibawaCenter.lat;
                lng = parseFloat(desa?.koordinat_longitude) || this.tibawaCenter.lng;
                lat += (Math.random() - 0.5) * 0.01;
                lng += (Math.random() - 0.5) * 0.01;
            }
            
            console.log(`Adding road ${jalan.nama_jalan} at [${lat}, ${lng}]`);
            
            const marker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: '#3498db',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(this.layerGroups.jalan);
            
            marker.bindPopup(`
                <div class="popup-content">
                    <h5>${jalan.nama_jalan}</h5>
                    <p><strong>Desa:</strong> ${jalan.nama_desa}</p>
                    <p><strong>Panjang:</strong> ${jalan.panjang_jalan} km</p>
                    <p><strong>Lebar:</strong> ${jalan.lebar_jalan || 'N/A'} m</p>
                    <p><strong>Kondisi:</strong> ${jalan.kondisi_jalan}</p>
                    <p><strong>Permukaan:</strong> ${jalan.jenis_permukaan}</p>
                </div>
            `);
            
            this.markers.push({
                type: 'jalan',
                id: jalan.id_jalan,
                marker: marker
            });
        });
    }
    
    loadBridges() {
        console.log('Loading bridges...');
        window.petaData.jembatan.forEach(jembatan => {
            // Use coordinates from database or fallback
            let lat = parseFloat(jembatan.koordinat_latitude);
            let lng = parseFloat(jembatan.koordinat_longitude);
            
            if (!lat || !lng) {
                const desa = window.petaData.desa.find(d => d.nama_desa === jembatan.nama_desa);
                lat = parseFloat(desa?.koordinat_latitude) || this.tibawaCenter.lat;
                lng = parseFloat(desa?.koordinat_longitude) || this.tibawaCenter.lng;
                lat += (Math.random() - 0.5) * 0.01;
                lng += (Math.random() - 0.5) * 0.01;
            }
            
            console.log(`Adding bridge ${jembatan.nama_jembatan} at [${lat}, ${lng}]`);
            
            const marker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: '#f39c12',
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(this.layerGroups.jembatan);
            
            marker.bindPopup(`
                <div class="popup-content">
                    <h5>${jembatan.nama_jembatan}</h5>
                    <p><strong>Desa:</strong> ${jembatan.nama_desa}</p>
                    <p><strong>Panjang:</strong> ${jembatan.panjang_jembatan} m</p>
                    <p><strong>Lebar:</strong> ${jembatan.lebar_jembatan || 'N/A'} m</p>
                    <p><strong>Kondisi:</strong> ${jembatan.kondisi_jembatan}</p>
                    <p><strong>Material:</strong> ${jembatan.material_jembatan || 'N/A'}</p>
                </div>
            `);
            
            this.markers.push({
                type: 'jembatan',
                id: jembatan.id_jembatan,
                marker: marker
            });
        });
    }
    
    bindEvents() {
        this.initSearchAndPagination();
    }
    
    initSearchAndPagination() {
        // Initialize pagination data
        this.pagination = {
            desa: { currentPage: 1, itemsPerPage: 10, filteredRows: [] },
            fasilitas: { currentPage: 1, itemsPerPage: 10, filteredRows: [] },
            jalan: { currentPage: 1, itemsPerPage: 10, filteredRows: [] },
            jembatan: { currentPage: 1, itemsPerPage: 10, filteredRows: [] }
        };
        
        // Setup search and pagination for each table
        this.setupTableFeatures('desa', 'searchDesa', 'desaTable');
        this.setupTableFeatures('fasilitas', 'searchFasilitas', 'fasilitasTable');
        this.setupTableFeatures('jalan', 'searchJalan', 'jalanTable');
        this.setupTableFeatures('jembatan', 'searchJembatan', 'jembatanTable');
    }
    
    setupTableFeatures(type, searchId, tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        this.pagination[type].filteredRows = rows;
        
        // Setup search
        const searchInput = document.getElementById(searchId);
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const filteredRows = rows.filter(row => {
                    return row.textContent.toLowerCase().includes(searchTerm);
                });
                
                this.pagination[type].filteredRows = filteredRows;
                this.pagination[type].currentPage = 1;
                this.updateTableDisplay(type, tableId);
            });
        }
        
        // Setup items per page
        const perPageSelect = document.getElementById(`${type}PerPage`);
        if (perPageSelect) {
            perPageSelect.addEventListener('change', (e) => {
                this.pagination[type].itemsPerPage = parseInt(e.target.value);
                this.pagination[type].currentPage = 1;
                this.updateTableDisplay(type, tableId);
            });
        }
        
        this.updateTableDisplay(type, tableId);
    }
    
    updateTableDisplay(type, tableId) {
        const table = document.getElementById(tableId);
        const pagination = this.pagination[type];
        const { currentPage, itemsPerPage, filteredRows } = pagination;
        
        const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        // Hide all rows
        const allRows = table.querySelectorAll('tbody tr');
        allRows.forEach(row => row.style.display = 'none');
        
        // Show filtered rows for current page
        filteredRows.slice(startIndex, endIndex).forEach(row => {
            row.style.display = '';
        });
        
        // Update pagination info
        const startItem = filteredRows.length > 0 ? startIndex + 1 : 0;
        const endItem = Math.min(endIndex, filteredRows.length);
        
        document.getElementById(`${type}Start`).textContent = startItem;
        document.getElementById(`${type}End`).textContent = endItem;
        document.getElementById(`${type}Total`).textContent = filteredRows.length;
        document.getElementById(`${type}PageInfo`).textContent = `Halaman ${currentPage} dari ${Math.max(totalPages, 1)}`;
        
        // Update buttons
        const prevBtn = document.getElementById(`${type}Prev`);
        const nextBtn = document.getElementById(`${type}Next`);
        
        if (prevBtn) prevBtn.disabled = currentPage <= 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages || totalPages <= 1;
    }
    

    

    
    resetMapView() {
        this.map.setView([this.tibawaCenter.lat, this.tibawaCenter.lng], 12);
    }
    
    toggleFullscreen() {
        const mapContainer = document.querySelector('.map-container');
        if (!document.fullscreenElement) {
            mapContainer.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    }
    
    showOnMap(type, id) {
        // Scroll to map first
        document.querySelector('.peta-admin-map-section').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
        
        if (type === 'desa') {
            // Find desa name from data
            const desa = window.petaData.desa.find(d => d.id_desa == id);
            if (desa) {
                this.showDesaBoundary(desa.nama_desa);
                return;
            }
        }
        
        const markerData = this.markers.find(m => m.type === type && m.id == id);
        if (markerData) {
            const marker = markerData.marker;
            
            // Pan to marker
            if (marker.getLatLng) {
                this.map.setView(marker.getLatLng(), 16);
            } else if (marker.getBounds) {
                this.map.fitBounds(marker.getBounds());
            }
            
            // Open popup
            marker.openPopup();
            
            // Highlight marker temporarily
            setTimeout(() => {
                if (marker.getElement) {
                    const element = marker.getElement();
                    element.style.animation = 'bounce 1s ease-in-out';
                }
            }, 500);
        }
    }
    
    async showDesaBoundary(desaName) {
        try {
            const response = await fetch(window.BASE_URL + 'api/administrative-boundaries.php');
            const boundaries = await response.json();
            
            // Find matching boundary (case insensitive)
            const matchingBoundary = boundaries.find(b => {
                if (b.boundary_type !== 'desa') return false;
                const boundaryName = (b.nm_desa || b.nm_kelurahan || '').toLowerCase().trim();
                const searchName = desaName.toLowerCase().trim();
                return boundaryName === searchName;
            });
            
            if (matchingBoundary) {
                const geojson = JSON.parse(matchingBoundary.geojson_data);
                const tempLayer = L.geoJSON(geojson);
                const bounds = tempLayer.getBounds();
                
                // Zoom to boundary
                this.map.fitBounds(bounds, { padding: [20, 20] });
                
                // Highlight boundary temporarily
                const highlightLayer = L.geoJSON(geojson, {
                    style: {
                        color: '#ff0000',
                        weight: 4,
                        fillOpacity: 0.3
                    }
                }).addTo(this.map);
                
                setTimeout(() => {
                    this.map.removeLayer(highlightLayer);
                }, 3000);
            }
        } catch (error) {
            console.error('Error finding boundary:', error);
        }
    }
    

}

// Global functions for template
function resetMapView() {
    if (window.petaAdmin) {
        petaAdmin.resetMapView();
    }
}

function toggleLayer(layerType) {
    if (window.petaAdmin && window.petaAdmin.layerGroups[layerType]) {
        const layerGroup = petaAdmin.layerGroups[layerType];
        const legendItem = document.querySelector(`[data-layer="${layerType}"]`);
        
        if (petaAdmin.map.hasLayer(layerGroup)) {
            petaAdmin.map.removeLayer(layerGroup);
            legendItem.style.opacity = '0.5';
        } else {
            petaAdmin.map.addLayer(layerGroup);
            legendItem.style.opacity = '1';
        }
    }
}

function showOnMap(type, id) {
    if (window.petaAdmin) {
        petaAdmin.showOnMap(type, id);
    }
}

function changePage(type, direction) {
    if (window.petaAdmin && window.petaAdmin.pagination) {
        const pagination = petaAdmin.pagination[type];
        const totalPages = Math.ceil(pagination.filteredRows.length / pagination.itemsPerPage);
        
        if (direction === 'prev' && pagination.currentPage > 1) {
            pagination.currentPage--;
        } else if (direction === 'next' && pagination.currentPage < totalPages) {
            pagination.currentPage++;
        }
        
        petaAdmin.updateTableDisplay(type, `${type}Table`);
    }
}

function changeItemsPerPage(type, value) {
    if (window.petaAdmin && window.petaAdmin.pagination) {
        petaAdmin.pagination[type].itemsPerPage = parseInt(value);
        petaAdmin.pagination[type].currentPage = 1;
        petaAdmin.updateTableDisplay(type, `${type}Table`);
    }
}

function toggleFullscreen() {
    if (window.petaAdmin) {
        petaAdmin.toggleFullscreen();
    }
}

function toggleTibawaFilter() {
    if (window.petaAdmin) {
        petaAdmin.tibawaFilter = !petaAdmin.tibawaFilter;
        const btn = document.getElementById('tibawaFilterBtn');
        
        if (petaAdmin.tibawaFilter) {
            btn.style.backgroundColor = '#3F72AF';
            btn.style.color = 'white';
        } else {
            btn.style.backgroundColor = '';
            btn.style.color = '';
        }
        
        petaAdmin.renderBoundaries();
    }
}

// Administrative Boundaries upload functions
function uploadAdministrativeBoundaries() {
    document.getElementById('uploadAdministrativeBoundariesModal').style.display = 'flex';
}

function closeAdministrativeBoundariesModal() {
    document.getElementById('uploadAdministrativeBoundariesModal').style.display = 'none';
    document.getElementById('administrativeBoundariesFile').value = '';
    document.getElementById('administrativeBoundariesUploadStatus').style.display = 'none';
    document.getElementById('boundariesPreviewInfo').style.display = 'none';
}

// Preview GeoJSON when file selected
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('administrativeBoundariesFile');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const geojson = JSON.parse(e.target.result);
                        if (geojson.type === 'FeatureCollection' && geojson.features.length > 0) {
                            const types = {};
                            geojson.features.forEach(feature => {
                                const props = feature.properties || {};
                                // Try to detect boundary type from properties
                                if (props.NAMOBJ || props.nama_desa || props.desa) types.desa = (types.desa || 0) + 1;
                                if (props.NAMKEC || props.kecamatan) types.kecamatan = (types.kecamatan || 0) + 1;
                                if (props.NAMKAB || props.kabupaten) types.kabupaten = (types.kabupaten || 0) + 1;
                                if (props.NAMPROV || props.provinsi) types.provinsi = (types.provinsi || 0) + 1;
                            });
                            
                            let preview = `<small>Ditemukan ${geojson.features.length} features.<br>`;
                            preview += `Tipe: ${Object.entries(types).map(([k,v]) => `${k}: ${v}`).join(', ')}</small>`;
                            
                            document.getElementById('boundariesPreviewContent').innerHTML = preview;
                            document.getElementById('boundariesPreviewInfo').style.display = 'block';
                        }
                    } catch (error) {
                        console.log('Preview error:', error);
                    }
                };
                reader.readAsText(file);
            }
        });
    }
});

async function processAdministrativeBoundaries() {
    const fileInput = document.getElementById('administrativeBoundariesFile');
    const statusDiv = document.getElementById('administrativeBoundariesUploadStatus');
    
    if (!fileInput.files[0]) {
        statusDiv.innerHTML = 'Pilih file GeoJSON terlebih dahulu';
        statusDiv.style.background = '#f8d7da';
        statusDiv.style.color = '#721c24';
        statusDiv.style.display = 'block';
        return;
    }
    
    const formData = new FormData();
    formData.append('administrative_boundaries_file', fileInput.files[0]);
    
    try {
        statusDiv.innerHTML = 'Memproses batas administratif...';
        statusDiv.style.background = '#d1ecf1';
        statusDiv.style.color = '#0c5460';
        statusDiv.style.display = 'block';
        
        const response = await fetch(window.BASE_URL + 'pages/admin/peta/upload-administrative-boundaries.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            statusDiv.innerHTML = `Berhasil! ${result.inserted} batas administratif diproses.`;
            statusDiv.style.background = '#d4edda';
            statusDiv.style.color = '#155724';
            
            setTimeout(() => {
                closeAdministrativeBoundariesModal();
                location.reload();
            }, 3000);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        statusDiv.innerHTML = 'Error: ' + error.message;
        statusDiv.style.background = '#f8d7da';
        statusDiv.style.color = '#721c24';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the peta page
    if (document.querySelector('.peta-admin-container')) {
        console.log('Initializing Peta Admin...');
        window.petaAdmin = new PetaAdminComponent();
    }
});

// Also initialize if DOM is already loaded
if (document.readyState !== 'loading' && document.querySelector('.peta-admin-container')) {
    console.log('DOM already loaded, initializing Peta Admin...');
    window.petaAdmin = new PetaAdminComponent();
}

// Add custom marker styles
const style = document.createElement('style');
style.textContent = `
    .custom-marker {
        background: white;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: white;
    }
    
    .facility-marker {
        background: #e74c3c;
    }
    
    .bridge-marker {
        background: #f39c12;
    }
    
    .temp-marker {
        background: #9b59b6;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }
    
    .popup-content h5 {
        color: #112D4E;
        margin-bottom: 12px;
        font-weight: 600;
    }
    
    .popup-content p {
        margin: 4px 0;
        font-size: 14px;
    }
    
    .kecamatan-label {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .label-text {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #2980b9;
        border-radius: 4px;
        padding: 4px 8px;
        font-weight: bold;
        font-size: 12px;
        color: #2980b9;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .label-text-kabupaten {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #e67e22;
        border-radius: 4px;
        padding: 6px 10px;
        font-weight: bold;
        font-size: 14px;
        color: #e67e22;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .label-text-desa {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #1abc9c;
        border-radius: 3px;
        padding: 2px 6px;
        font-weight: bold;
        font-size: 10px;
        color: #1abc9c;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    
    .kabupaten-label, .desa-label {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
    }
`;
document.head.appendChild(style);
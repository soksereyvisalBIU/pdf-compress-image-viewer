<template>
    <div class="pdf-viewer" @scroll="handleScroll" ref="pdfViewer">
        <div class="spacer" :style="{ height: totalHeight + 'px' }"></div>
        <div v-for="page in visiblePages" :key="page.number" :style="getPageStyle(page)" class="page-container">
            <img :src="page.url" :alt="`Page ${page.number}`" class="page-image" :style="imageStyle" />
        </div>
        <div v-if="loading" class="loading">Loading more pages...</div>
        <div v-if="noMorePages" class="no-more-pages">End of document</div>

        <!-- Toolbar -->
        <div class="toolbar">
            <button @click="zoomIn">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </button>
            <button @click="zoomOut">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </button>
            <input v-model="currentPage" type="number" @change="goToPage" />
            <span>/ {{ totalPages }}</span>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "PdfViewer",
    props: {
        pdfId: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            pages: [],
            currentPage: 1,
            totalPages: 0,
            loading: false,
            noMorePages: false,
            zoom: 1,
            rotation: 0,
            visiblePages: [],
            pageHeight: 900, // Approximate height of a single page (in pixels)
            bufferPages: 5, // Number of pages to render above and below the viewport
            lastRequestedPage: 0, // Track the last page we've requested
        };
    },
    computed: {
        imageStyle() {
            return {
                transform: `scale(${this.zoom}) rotate(${this.rotation}deg)`,
            };
        },
        totalHeight() {
            return this.pageHeight * this.totalPages * this.zoom;
        },
    },
    mounted() {
        this.loadMorePages();
        this.$nextTick(() => {
            this.handleScroll(); // Ensure scroll handler is called after DOM mount
        });
    },
    methods: {
        async loadMorePages() {
            if (this.loading || this.noMorePages) return;

            this.loading = true;
            const pagesToLoad = 20; // Load 20 pages at a time

            try {
                const response = await axios.get(`/api/pdf/${encodeURIComponent(this.pdfId)}/pages`, {
                    params: {
                        start: this.lastRequestedPage + 1,
                        count: 20,
                    },
                });

                const newPages = response.data.pages;

                console.log(newPages);
                
                this.pages.push(...newPages); // Append new pages
                this.totalPages = response.data.totalPages;

                if (newPages.length < pagesToLoad) {
                    this.noMorePages = true; // No more pages to load
                }

                this.lastRequestedPage = this.pages.length; // Update last page loaded
                this.updateVisiblePages(); // Update visible pages
            } catch (error) {
                console.error("Error loading pages:", error);
            } finally {
                this.loading = false;
            }
        },
        async loadPageRange(targetPage) {
            this.loading = true;
            const rangeSize = 20; // Define how many pages to load in a batch
            const startPage = Math.max(1, targetPage - Math.floor(rangeSize / 2));
            const endPage = Math.min(this.totalPages, startPage + rangeSize - 1);

            try {
                const response = await axios.get(`/api/pdf/${encodeURIComponent(this.pdfId)}/pages`, {
                    params: {
                        start: startPage,
                        count: rangeSize,
                    },
                });

                const newPages = response.data.pages;
                this.pages.push(...newPages);

                // Ensure the new pages are appended and update the scroll position
                this.$nextTick(() => {
                    this.$refs.pdfViewer.scrollTop = (this.currentPage - 1) * this.pageHeight * this.zoom;
                });

                this.updateVisiblePages(); // Update visible pages after loading new range
            } catch (error) {
                console.error("Error loading specific page range:", error);
                alert("Failed to load the page. Please try again later.");
            } finally {
                this.loading = false;
            }
        },
        updateVisiblePages() {
            const container = this.$refs.pdfViewer;
            const scrollTop = container.scrollTop;
            const containerHeight = container.clientHeight;

            // Calculate the first and last visible page index
            const firstVisiblePage = Math.floor(
                scrollTop / (this.pageHeight * this.zoom)
            );
            const lastVisiblePage = Math.ceil(
                (scrollTop + containerHeight) / (this.pageHeight * this.zoom)
            );

            // Determine which pages to render (including buffer pages)
            const start = Math.max(0, firstVisiblePage - this.bufferPages);
            const end = Math.min(
                this.pages.length,
                lastVisiblePage + this.bufferPages
            );

            // Make sure to filter out undefined pages
            this.visiblePages = this.pages.slice(start, end).filter(page => page && page.number);

            // Find the page closest to the middle of the screen
            const middleOfScreen = scrollTop + containerHeight / 2;
            let closestPage = this.pages[0] || {};
            let closestDistance = Math.abs(
                middleOfScreen - (closestPage.number * this.pageHeight * this.zoom)
            );

            for (let page of this.pages) {
                if (!page) continue;  // Ensure page is not undefined

                const pageTop = (page.number - 1) * this.pageHeight * this.zoom;
                const distance = Math.abs(middleOfScreen - pageTop);
                if (distance < closestDistance) {
                    closestPage = page;
                    closestDistance = distance;
                }
            }

            // Update the current page number to the closest visible page
            if (closestPage.number) {
                this.currentPage = closestPage.number;
            }

            // Load more pages when the current page is near the end of the last requested batch
            if (
                this.currentPage >= this.lastRequestedPage - 5 &&
                !this.noMorePages
            ) {
                this.loadMorePages();
            }
        },
        handleScroll() {
            const container = this.$refs.pdfViewer;
            const scrollTop = container.scrollTop;
            const containerHeight = container.clientHeight;
            const scrollHeight = container.scrollHeight;

            if (scrollTop + containerHeight >= scrollHeight - 200) {
                this.loadMorePages(); // Load more pages when nearing the bottom
            }

            // Debounced update to avoid recalculating on every pixel of scroll
            this.debounce(this.updateVisiblePages, 100)();
        },
        getPageStyle(page) {
            const pageIndex = page.number - 1;
            return {
                position: "absolute",
                top: pageIndex * this.pageHeight * this.zoom + "px",
                left: "0",
                right: "0",
                textAlign: "center",
            };
        },
        zoomIn() {
            this.zoom = Math.min(this.zoom * 1.2, 3); // Max zoom level
            this.updateVisiblePages(); // Recalculate visible pages after zooming
        },
        zoomOut() {
            this.zoom = Math.max(this.zoom / 1.2, 0.5); // Min zoom level
            this.updateVisiblePages(); // Recalculate visible pages after zooming
        },
        goToPage() {
            if (this.currentPage < 1) this.currentPage = 1;
            if (this.currentPage > this.totalPages) this.currentPage = this.totalPages;

            const targetPage = this.pages.find((page) => page.number === this.currentPage);
            if (targetPage) {
                this.$refs.pdfViewer.scrollTop = (this.currentPage - 1) * this.pageHeight * this.zoom;
            } else {
                this.loadPageRange(this.currentPage);
            }
        },
        debounce(fn, delay) {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn(...args), delay);
            };
        },
    },
};
</script>



<style scoped>
.pdf-viewer {
    height: 100dvh;
    overflow-y: auto;
    position: relative;
    width: 100%;
}

.page-container {
    position: absolute;
    left: 0;
    right: 0;
    text-align: center;
}

.page-image {
    max-width: 100%;
    height: auto;
    transition: transform 0.3s ease;
    box-shadow: 0px 3px 5px 2px rgba(128, 128, 128, 0.561);
    margin-block: 10px;
}

.loading,
.no-more-pages {
    text-align: center;
    padding: 20px;
}

.toolbar {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(255, 255, 255, 0.8);
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.toolbar button {
    margin: 0 5px;
}

.toolbar input {
    width: 50px;
    text-align: center;
}

.spacer {
    position: relative;
    width: 100%;
}
</style>

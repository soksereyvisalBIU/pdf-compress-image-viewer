<template>
    <div class="pdf-viewer" @scroll="handleScroll" ref="pdfViewer">
        <div class="spacer" :style="{ height: totalHeight + 'px' }"></div>
        <div
            v-for="page in visiblePages"
            :key="page.number"
            :style="getPageStyle(page)"
            class="page-container"
        >
            <img
                :src="page.url"
                :alt="`Page ${page.number}`"
                class="page-image"
                :style="imageStyle"
            />
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
            <!-- <button @click="rotateClockwise">Rotate Clockwise</button>
            <button @click="rotateCounterClockwise">
                Rotate Counter-Clockwise
            </button> -->

            <button><i class="fa fa-angle-left"></i></button>
            <input v-model="currentPage" type="number" @change="goToPage" />
            <button><i class="fa fa-angle-right"></i></button>
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
        this.handleScroll(); // Initial call to render visible pages
    },
    methods: {
        async loadMorePages() {
            if (this.loading || this.noMorePages) return;

            this.loading = true;
            try {
                const response = await axios.get(
                    `/api/pdf/${this.pdfId}/pages`,
                    {
                        params: {
                            start: this.lastRequestedPage + 1,
                            count: 20,
                        },
                    }
                );

                const newPages = response.data.pages;
                this.pages.push(...newPages);
                this.totalPages = response.data.totalPages;

                // console.log("loadmorepage")
                // console.log(response.data)

                if (newPages.length < 20) {
                    this.noMorePages = true;
                }

                // Update the last requested page
                this.lastRequestedPage = this.pages.length;

                // Update visible pages after loading more
                this.updateVisiblePages();
            } catch (error) {
                console.error("Error loading pages:", error);
                alert("Failed to load pages. Please try again later.");
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

            this.visiblePages = this.pages.slice(start, end);

            // Find the page closest to the middle of the screen
            const middleOfScreen = scrollTop + containerHeight / 2;
            let closestPage = this.pages[0];
            let closestDistance = Math.abs(
                middleOfScreen -
                    closestPage.number * this.pageHeight * this.zoom
            );

            for (let page of this.pages) {
                const pageTop = (page.number - 1) * this.pageHeight * this.zoom;
                const distance = Math.abs(middleOfScreen - pageTop);
                if (distance < closestDistance) {
                    closestPage = page;
                    closestDistance = distance;
                }
            }

            // Update the current page number to the closest visible page
            this.currentPage = closestPage.number;

            // Load more pages when the current page is near the end of the last requested batch
            if (
                this.currentPage >= this.lastRequestedPage - 5 &&
                !this.noMorePages
            ) {
                // console.log("hehe")
                this.loadMorePages();
            }
        },
        handleScroll() {
            const container = this.$refs.pdfViewer;
            const scrollTop = container.scrollTop;
            const containerHeight = container.clientHeight;
            const scrollHeight = container.scrollHeight;

            // Check if the user has scrolled near the bottom and load more pages
            if (
                !this.loading &&
                !this.noMorePages &&
                scrollTop + containerHeight >= scrollHeight - 200
            ) {
                this.loadMorePages();
            }

            this.updateVisiblePages();
        },

        // handleScroll() {
        //     const container = this.$refs.pdfViewer;
        //     const scrollTop = container.scrollTop;
        //     const containerHeight = container.clientHeight;
        //     const scrollHeight = container.scrollHeight;

        //     // Check if the user has scrolled near the bottom and load more pages
        //     if (
        //         !this.loading &&
        //         !this.noMorePages &&
        //         scrollTop + containerHeight >= scrollHeight - 200
        //     ) {
        //         this.loadMorePages();
        //     }
        //     else{
        //         console.log(scrollTop);
        //     }

        //     this.updateVisiblePages();
        // },
        // updateVisiblePages() {
        //     const container = this.$refs.pdfViewer;
        //     const scrollTop = container.scrollTop;
        //     const containerHeight = container.clientHeight;

        //     // Calculate the first and last visible page index
        //     const firstVisiblePage = Math.floor(
        //         scrollTop / (this.pageHeight * this.zoom)
        //     );
        //     const lastVisiblePage = Math.ceil(
        //         (scrollTop + containerHeight) / (this.pageHeight * this.zoom)
        //     );

        //     // Determine which pages to render (including buffer pages)
        //     const start = Math.max(0, firstVisiblePage - this.bufferPages);
        //     const end = Math.min(
        //         this.pages.length,
        //         lastVisiblePage + this.bufferPages
        //     );

        //     this.visiblePages = this.pages.slice(start, end);
        // },

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
            this.zoom = Math.min(this.zoom * 1.2, 3); // Limit max zoom level
            this.updateVisiblePages(); // Recalculate visible pages after zooming
        },
        zoomOut() {
            this.zoom = Math.max(this.zoom / 1.2, 0.5); // Limit min zoom level
            this.updateVisiblePages(); // Recalculate visible pages after zooming
        },
        rotateClockwise() {
            this.rotation = (this.rotation + 90) % 360;
        },
        rotateCounterClockwise() {
            this.rotation = (this.rotation - 90 + 360) % 360;
        },
        goToPage() {
            if (this.currentPage < 1) this.currentPage = 1;
            if (this.currentPage > this.totalPages)
                this.currentPage = this.totalPages;

            const targetPage = this.pages.find(
                (page) => page.number === this.currentPage
            );
            if (targetPage) {
                this.$refs.pdfViewer.scrollTop =
                    (this.currentPage - 1) * this.pageHeight * this.zoom;
            } else {
                this.loadPageRange(this.currentPage);
            }
        },
        async loadPageRange(targetPage) {
            this.loading = true;
            try {
                const response = await axios.get(
                    `/api/pdf/${this.pdfId}/pages`,
                    {
                        params: {
                            start: targetPage,
                            count: 20,
                        },
                    }
                );

                const newPages = response.data.pages;
                this.pages = newPages;
                this.$nextTick(() => {
                    this.$refs.pdfViewer.scrollTop = 0;
                });

                this.updateVisiblePages(); // Update visible pages after loading new range
            } catch (error) {
                console.error("Error loading specific page range:", error);
                alert("Failed to load the page. Please try again later.");
            } finally {
                this.loading = false;
            }
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

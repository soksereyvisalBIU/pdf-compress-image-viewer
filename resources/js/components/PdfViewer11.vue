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
            <button @click="zoomIn">Zoom In</button>
            <button @click="zoomOut">Zoom Out</button>
            <button @click="rotateClockwise">Rotate Clockwise</button>
            <button @click="rotateCounterClockwise">
                Rotate Counter-Clockwise
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
        pdfId: { type: String, required: true },
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
            pageHeight: 800,
            bufferPages: 5,
            lastRequestedPage: 0,
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
        this.handleScroll();
    },
    methods: {
        async loadMorePages() {
            if (this.loading || this.noMorePages) return;
            this.loading = true;
            try {
                const { data } = await axios.get(
                    `/api/pdf/${this.pdfId}/pages`,
                    {
                        params: {
                            start: this.lastRequestedPage + 1,
                            count: 20,
                        },
                    }
                );
                this.pages.push(...data.pages);
                this.totalPages = data.totalPages;
                if (data.pages.length < 20) this.noMorePages = true;
                this.lastRequestedPage = this.pages.length;
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

            const firstVisiblePage = Math.floor(
                scrollTop / (this.pageHeight * this.zoom)
            );
            const lastVisiblePage = Math.ceil(
                (scrollTop + containerHeight) / (this.pageHeight * this.zoom)
            );

            const start = Math.max(0, firstVisiblePage - this.bufferPages);
            const end = Math.min(
                this.pages.length,
                lastVisiblePage + this.bufferPages
            );
            this.visiblePages = this.pages.slice(start, end);

            this.currentPage = this.pages.reduce((closest, page) => {
                const pageTop = (page.number - 1) * this.pageHeight * this.zoom;
                const distance = Math.abs(
                    scrollTop + containerHeight / 2 - pageTop
                );
                return distance <
                    Math.abs(
                        scrollTop +
                            containerHeight / 2 -
                            (closest.number - 1) * this.pageHeight * this.zoom
                    )
                    ? page
                    : closest;
            }).number;

            if (
                this.currentPage >= this.lastRequestedPage - 5 &&
                !this.noMorePages
            ) {
                this.loadMorePages();
            }
        },
        handleScroll() {
            const container = this.$refs.pdfViewer;
            if (
                !this.loading &&
                !this.noMorePages &&
                container.scrollTop + container.clientHeight >=
                    container.scrollHeight - 200
            ) {
                this.loadMorePages();
            }
            this.updateVisiblePages();
        },
        zoomIn() {
            this.zoom = Math.min(this.zoom * 1.2, 3);
            this.updateVisiblePages();
        },
        zoomOut() {
            this.zoom = Math.max(this.zoom / 1.2, 0.5);
            this.updateVisiblePages();
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
                const { data } = await axios.get(
                    `/api/pdf/${this.pdfId}/pages`,
                    {
                        params: { start: targetPage, count: 20 },
                    }
                );
                this.pages = data.pages;
                this.$nextTick(() => (this.$refs.pdfViewer.scrollTop = 0));
                this.updateVisiblePages();
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
    height: 100vh;
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
}

.loading,
.no-more-pages {
    text-align: center;
    padding: 20px;
}

.toolbar {
    position: fixed;
    bottom: 20px;
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

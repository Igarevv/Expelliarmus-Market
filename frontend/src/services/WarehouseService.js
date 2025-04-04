import api from "@/utils/api.js";
import {useJsonApiFormatter} from "@/composables/useJsonApiFormatter.js";

const formatter = useJsonApiFormatter();

export const WarehouseService = {
    async searchProduct(searchable) {
        return await api().get(
            `/management/warehouse/products?search=${searchable}`,
        );
    },

    async getProduct(productId) {
        return await api()
            .get(
                `/management/warehouse/products/${productId}?include=warehouse,category,brand,variations`,
            )
            .then((response) => {
                const productAttr = response.data.data.attributes;

                const product = {
                    id: response.data.data.id,
                    title: productAttr.title,
                    article: productAttr.article,
                    status: productAttr.status,
                    variationType: productAttr.variationType,
                    previewImage: productAttr.previewImage,
                };

                const includedParsed = formatter.parseIncluded(response.data);

                return {
                    product: product,
                    category: includedParsed.category[0]["attributes"],
                    brand: includedParsed.brand[0]["attributes"],
                    warehouse: includedParsed.warehouse[0]["attributes"],
                    variations: includedParsed.variations,
                };
            })
            .catch((e) => {
                throw e;
            });
    },

    async getProductsTable(params) {
        const url = formatter.formatUrl("/management/warehouse/table", params, 'with');

        return await api().get(url);
    },

    async getDiscountedProduct(productId) {
        return await api().get(`management/discounts/products/${productId}?include=warehouse,variations,brand,category`)
            .then((response) => {
                const productAttr = response.data.data.attributes;

                const product = {
                    id: response.data.data.id,
                    title: productAttr.title,
                    article: productAttr.article,
                    status: productAttr.status,
                    variationType: productAttr.variationType,
                    previewImage: productAttr.previewImage,
                    discount: productAttr.discount
                };

                const includedParsed = formatter.parseIncluded(response.data);

                return {
                    product: product,
                    category: includedParsed.category[0]["attributes"],
                    brand: includedParsed.brand[0]["attributes"],
                    warehouse: includedParsed.warehouse[0]["attributes"],
                    variations: includedParsed.variations,
                };
            })
            .catch((e) => {
                throw e;
            });
    },

    async addDiscount(productId, discountInfo) {
        return await api().post(
            `/management/warehouse/products/${productId}/discounts`,
            formatter.toJsonApi(discountInfo, 'discounts')
        );
    },

    async updateDiscount(productId, discountId, discountInfo) {
        return await api().put(
            `/management/warehouse/products/${productId}/discounts/${discountId}`,
            formatter.toJsonApi(discountInfo, 'discounts')
        );
    },

    async cancelDiscount(productId, discountId) {
        return await api().delete(`/management/warehouse/products/${productId}/discounts/${discountId}`);
    },

    async discountedProducts(params) {
        const url = formatter.formatUrl("/management/warehouse/products/discounted", params);
        return await api().get(url);
    },
};

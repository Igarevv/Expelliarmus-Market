<?php

declare(strict_types=1);

namespace Modules\Product\Storages\ProductImages;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Intervention\Image\Image;
use Modules\Product\Http\Management\Contracts\Storage\S3ProductImagesStorageInterface;
use Modules\Product\Http\Management\DTO\Images\MainImageDto;
use Modules\Product\Http\Management\Exceptions\FailedToUploadImagesException;
use Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Throwable;

class S3ProductImagesStorage extends BaseProductImagesStorage implements S3ProductImagesStorageInterface
{
    private array $images = [];

    public function upload(UploadedFile $file, Product $product, ?string $hashName = null): string
    {
        try {
            $path = "products/product-id-$product->id-images";

            $this->storage->makeDirectory($path);

            $fileName = $hashName ?? $file->hashName();

            return $this->storage->putFileAs($path, $file, $fileName);
        } catch (Throwable $e) {
            throw new FileException($e->getMessage());
        }
    }

    /**
     * @throws FailedToUploadImagesException
     */
    public function uploadMany(Collection $files, Product $product): array
    {
        if ($files->isEmpty()) {
            $this->storage->copy(
                'products/'.$this->defaultImageId(),
                $this->getImageFullPath($product, $this->defaultImageId()),
            );

            return [
                [
                    'order' => 1,
                    'source' => $this->defaultImageId(),
                ],
            ];
        }

        try {
            $this->images = $files->map(function (MainImageDto $image) use ($product) {
                $this->upload($image->image, $product);

                return [
                    'order' => $image->order,
                    'source' => $image->image->hashName(),
                ];
            })->toArray();
        } catch (Throwable $e) {
            foreach ($this->images as $image) {
                $this->delete($product, $this->getImageFullPath($product, $image['source']));
            }

            throw new FailedToUploadImagesException($e->getMessage(), $e);
        }

        return $this->images;
    }

    public function getOne(Product $product, ?string $imageId): string
    {
        if ($this->isExists($product, $imageId)) {
            return $this->storage->url($this->getImageFullPath($product, $imageId));
        }

        return $this->storage->url($this->defaultImageId());
    }

    public function getAll(Product $product): array
    {
        if (count($product->images) === 1 && $product->images[0] === $this->defaultImageId()) {
            return [$this->getOne($product, $this->defaultImageId())];
        }

        return collect($product->images)->map(function (string $imageId) use ($product) {
            return $this->getOne($product, $imageId);
        })->toArray();
    }

    public function getAllFromSources(Product $product, array $imagesSources): array
    {
        return collect($imagesSources)->map(function (array $images) use ($product) {
            return [
                'order' => $images['order'],
                'image_url' => $this->storage->url($this->getImageFullPath($product, $images['image'])),
            ];
        })->toArray();
    }

    public function delete(Product $product, string $imageId): bool
    {
        return $this->storage->delete("products/".$this->getImageFullPath($product, $imageId));
    }

    public function deleteMany(Product $product, Collection $sources): void
    {
        $sources->each(function (string $source) use ($product) {
            $this->delete($product, $source);
        });
    }

    protected function getInterventionPreviewImage(Product $product, string $imageId): Image
    {
        if ($imageId === $this->defaultPreviewImage()) {
            $this->storage->copy(
                'products/'.$imageId,
                $this->getImageFullPath($product, $imageId),
            );
        }

        $imageContent = $this->storage->get($this->getImageFullPath($product, $imageId));

        if (! $imageContent) {
            throw new FileException('Failed to get image content');
        }

        return $this->imageManager->read($imageContent);
    }

    protected function getImageFullPath(Product $product, string $imageId): string
    {
        return "products/product-id-$product->id-images/$imageId";
    }
}
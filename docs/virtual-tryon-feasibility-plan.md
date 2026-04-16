# Virtual Try-On Feasibility Plan for Pesca.uz

## 1) Short answer

Yes, this can be applied in the current project, but not as an in-process PHP feature.
The practical architecture is:

- Symfony app (current project) as orchestrator and UI
- Separate AI inference service (Python + GPU) for segmentation + try-on
- Async job flow (Messenger queue) to avoid request timeouts

## 2) Current project readiness

Ready parts:
- Product image URL/file support already exists.
- User profile and style metadata model already exists.
- Messenger transport is configured and can be extended for AI jobs.
- Upload path exists and can store generated images.

Missing parts:
- No AI model runtime in app containers
- No Python/PyTorch service
- No queue consumer for custom AI messages
- No persistence model for try-on jobs/results

## 3) Core challenge and correct strategy

Core challenge:
- Marketplace images are usually model-worn garments, not clean isolated garments.

Correct strategy:
1. Garment extraction via human parsing/segmentation
2. Person image preprocessing
3. Virtual try-on inference
4. Result QA + storage

Important:
- Background removal only is not enough.
- Need clothing region masks (top/dress/lower) from parsing model.

## 4) Proposed architecture for this repository

### A. New AI microservice (separate container/repo or service folder)

Responsibilities:
- Download and normalize input images
- Run segmentation/parsing for garment extraction
- Run try-on model
- Upload generated result
- Return status/progress/errors

Suggested endpoints:
- POST /jobs/tryon
- GET /jobs/{jobId}
- GET /jobs/{jobId}/result

### B. Symfony integration layer

New responsibilities inside this project:
- Receive user image
- Start try-on job
- Poll/show status
- Persist metadata
- Show results in product page/profile history

## 5) Concrete integration points in current codebase

1. Product source image pipeline:
- src/Entity/Product.php
- src/Form/ProductType.php
- src/Controller/Admin/ProductCrudController.php
- src/Controller/VendorController.php

2. User image/profile context:
- src/Entity/UserProfile.php
- src/Controller/ProfileController.php

3. Product detail trigger point:
- src/Controller/ProductController.php
- templates/product/show.html.twig

4. Queue and async execution:
- config/packages/messenger.yaml

5. Storage path for generated outputs:
- public/uploads

## 6) Required data model additions (Symfony)

Add a new entity: VirtualTryOnJob

Suggested fields:
- id
- user_id
- product_id
- source_product_image
- source_user_image
- segmentation_status
- inference_status
- status (queued, processing, done, failed)
- provider_job_id
- error_message
- result_image_path
- created_at, updated_at

Optional second entity: VirtualTryOnAsset
- Store intermediate masks, parsed garment, debug artifacts.

## 7) Runtime and infrastructure reality check

For production-quality speed:
- GPU strongly recommended
- Suggested minimum for stable throughput: 16GB RAM + 1 GPU (>= 8GB VRAM preferred)

If only CPU VDS:
- Can work for prototype
- Latency will be too high for user-facing synchronous UX
- Must use queue + delayed results

## 8) Implementation phases (recommended)

Phase 0 (1-2 days):
- Build external PoC in Colab using fixed sample images
- Validate extraction quality on marketplace-like photos

Phase 1 (3-5 days):
- Stand up AI service with one segmentation pipeline + one try-on model
- Expose REST API

Phase 2 (2-4 days):
- Add Symfony job entity + migration
- Add API client service in Symfony
- Add Messenger message + handler
- Add result polling endpoint

Phase 3 (2-3 days):
- Add UI: Upload image + Try-on button on product page
- Add status/result widget

Phase 4 (2-4 days):
- Quality filters (face/pose checks)
- Error handling, retries, timeouts
- Basic moderation/logging

## 9) Product policy to reduce failure rate

Recommended ingestion policy:
- Prefer clean catalog images first
- Marketplace model-worn images go through segmentation path
- Reject low-confidence extraction automatically

This hybrid policy avoids poor results and protects UX.

## 10) Risks and mitigation

1. Low extraction quality on complex clothing
- Mitigate with confidence threshold + fallback to clean-catalog-only mode

2. Long inference times
- Mitigate with async queue and result-not-ready UX

3. Ops complexity
- Mitigate by isolating AI runtime from PHP app

4. Cost growth
- Mitigate with caching results by (user_image_hash + product_id)

## 11) Final recommendation for Pesca.uz

Recommended go path:
1. Start with Colab PoC quality gate
2. Integrate as external AI microservice, not inside php container
3. Launch beta only for selected product types first
4. Keep fallback UX when segmentation confidence is low

Conclusion:
- Technically feasible for this project
- Should be implemented as staged architecture, not a single-step feature
- Existing Symfony structure is suitable as control plane and UI layer

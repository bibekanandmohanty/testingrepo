import { TestBed } from '@angular/core/testing';

import { ProductionStatusUpdateService } from './production-status-update.service';

describe('ProductionStatusUpdateService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ProductionStatusUpdateService = TestBed.get(ProductionStatusUpdateService);
    expect(service).toBeTruthy();
  });
});

import { TestBed } from '@angular/core/testing';

import { ArtWorkApprovalService } from './art-work-approval.service';

describe('ArtWorkApprovalService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ArtWorkApprovalService = TestBed.get(ArtWorkApprovalService);
    expect(service).toBeTruthy();
  });
});

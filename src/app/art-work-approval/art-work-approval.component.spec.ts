import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ArtWorkApprovalComponent } from './art-work-approval.component';

describe('ArtWorkApprovalComponent', () => {
  let component: ArtWorkApprovalComponent;
  let fixture: ComponentFixture<ArtWorkApprovalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ArtWorkApprovalComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ArtWorkApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

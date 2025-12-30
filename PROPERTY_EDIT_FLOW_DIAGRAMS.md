# Property Edit Flow Diagrams

## Overview
Visual representations of the property edit approval system using flow diagrams.

## 1. Main Decision Tree

```mermaid
flowchart TD
    Start([User Submits Property Edit]) --> CheckUser{Is Admin?<br/>added_by == 0}
    
    CheckUser -->|Yes| AdminFlow[Save All Fields Immediately]
    AdminFlow --> AdminResult[Property Status: Approved<br/>All Changes Visible]
    
    CheckUser -->|No| CheckAutoApprove{Auto-Approve ON?<br/>auto_approve_edited_listings == 1}
    
    CheckAutoApprove -->|Yes| AutoApproveFlow[Save All Fields Immediately]
    AutoApproveFlow --> AutoApproveResult[Property Status: Approved<br/>All Changes Visible<br/>No Edit Request]
    
    CheckAutoApprove -->|No| CheckApprovalFields{Approval-Required<br/>Fields Changed?}
    
    CheckApprovalFields -->|Yes| CreateEditRequest[Create Edit Request]
    CreateEditRequest --> SaveNonApproval[Save Non-Approval Fields<br/>Immediately]
    SaveNonApproval --> PendingResult[Property Status: Pending<br/>Approval Fields: Pending<br/>Non-Approval Fields: Saved]
    
    CheckApprovalFields -->|No| DirectSave[Save All Fields Immediately]
    DirectSave --> DirectSaveResult[Property Status: Approved<br/>All Changes Visible<br/>No Edit Request]
    
    AdminResult --> End([End])
    AutoApproveResult --> End
    PendingResult --> End
    DirectSaveResult --> End
```

## 2. Field Classification Flow

```mermaid
flowchart LR
    UserEdit[User Edits Field] --> CheckType{Field Type?}
    
    CheckType -->|Approval Required| ApprovalFields[Title, Description,<br/>Area Description, Images,<br/>Hotel Room Descriptions]
    CheckType -->|Auto-Approval| AutoFields[Price, Facilities,<br/>Parameters, Property Type,<br/>Hotel Settings, etc.]
    
    ApprovalFields --> ApprovalFlow{Approval<br/>Required?}
    AutoFields --> AutoFlow[Save Immediately]
    
    ApprovalFlow -->|Yes| CreateRequest[Create Edit Request]
    ApprovalFlow -->|No| SaveImmediate[Save Immediately]
    
    CreateRequest --> PendingStatus[Status: Pending]
    SaveImmediate --> ApprovedStatus[Status: Approved]
    AutoFlow --> ApprovedStatus
    
    PendingStatus --> AdminReview[Admin Reviews]
    AdminReview --> Approved[Approved]
    AdminReview --> Rejected[Rejected]
    
    Approved --> ApprovedStatus
    Rejected --> OriginalState[Changes Reverted]
```

## 3. Complete Edit Process Flow

```mermaid
sequenceDiagram
    participant User
    participant Frontend
    participant Backend
    participant Database
    participant Admin
    
    User->>Frontend: Edit Property Form
    User->>Frontend: Change Fields (Title, Price, Facilities)
    User->>Frontend: Click Submit
    
    Frontend->>Frontend: hasApprovalRequiredChanges()
    Frontend->>Frontend: getChangedApprovalFields()
    Frontend->>Backend: API Request with all changes
    
    Backend->>Backend: Check isOwnerEdit
    Backend->>Backend: Check autoApproveEdited
    Backend->>Backend: hasApprovalRequiredChanges()
    
    alt Approval Required
        Backend->>Database: Create PropertyEditRequest
        Backend->>Database: Save Property (status: pending)
        Backend->>Database: Save Facilities (immediate)
        Backend->>Database: Save Price (immediate)
        Backend->>Frontend: Response (edit_request + property)
        Frontend->>User: Show Approval Popup
        User->>Admin: Wait for Approval
        Admin->>Backend: Approve/Reject Request
        Backend->>Database: Update Property (if approved)
        Backend->>User: Notification
    else No Approval Required
        Backend->>Database: Save Property (status: approved)
        Backend->>Database: Save All Fields
        Backend->>Frontend: Response (success)
        Frontend->>User: Show Success Popup
    end
```

## 4. Field Change Detection Flow

```mermaid
flowchart TD
    Start([Form Submission]) --> GetFormData[Get Form Data]
    GetFormData --> GetOriginal[Get Original Property Data]
    
    GetOriginal --> CheckTitle{Title Changed?}
    CheckTitle -->|Yes| AddToApproval[Add to Approval List]
    CheckTitle -->|No| CheckDesc{Description Changed?}
    
    CheckDesc -->|Yes| AddToApproval
    CheckDesc -->|No| CheckAreaDesc{Area Description Changed?}
    
    CheckAreaDesc -->|Yes| AddToApproval
    CheckAreaDesc -->|No| CheckImages{Images Uploaded?}
    
    CheckImages -->|Yes| AddToApproval
    CheckImages -->|No| CheckHotelRooms{Hotel Room<br/>Descriptions Changed?}
    
    CheckHotelRooms -->|Yes| AddToApproval
    CheckHotelRooms -->|No| CheckPrice{Price Changed?}
    
    CheckPrice -->|Yes| AddToAuto[Add to Auto-Approval List]
    CheckPrice -->|No| CheckFacilities{Facilities Changed?}
    
    CheckFacilities -->|Yes| AddToAuto
    CheckFacilities -->|No| CheckOther{Other Fields Changed?}
    
    CheckOther -->|Yes| AddToAuto
    CheckOther -->|No| EndCheck[End Check]
    
    AddToApproval --> HasApprovalFields{Has Approval<br/>Fields?}
    AddToAuto --> HasApprovalFields
    
    HasApprovalFields -->|Yes| RequiresApproval[Requires Approval = true]
    HasApprovalFields -->|No| NoApproval[Requires Approval = false]
    
    RequiresApproval --> EndCheck
    NoApproval --> EndCheck
    EndCheck --> ReturnResult[Return Result]
```

## 5. Approval Request Lifecycle

```mermaid
stateDiagram-v2
    [*] --> UserSubmits: User edits property
    
    UserSubmits --> CheckFields: Submit form
    
    CheckFields --> Pending: Approval fields changed
    CheckFields --> Approved: Only auto-approval fields
    
    Pending --> AdminReview: Edit request created
    
    AdminReview --> Approved: Admin approves
    AdminReview --> Rejected: Admin rejects
    
    Approved --> ChangesApplied: Changes visible on listing
    Rejected --> ChangesReverted: Changes discarded
    
    ChangesApplied --> [*]
    ChangesReverted --> [*]
    
    note right of Pending
        Property status: pending
        Edit request created
        Non-approval fields saved
    end note
    
    note right of Approved
        Property status: approved
        All changes visible
    end note
```

## 6. User Type Comparison

```mermaid
flowchart TB
    subgraph AdminUser[Admin User]
        A1[Edit Property] --> A2[All Fields Editable]
        A2 --> A3[Save Directly]
        A3 --> A4[Status: Approved]
        A4 --> A5[Changes Visible Immediately]
    end
    
    subgraph OwnerAutoOn[Owner - Auto-Approve ON]
        O1[Edit Property] --> O2[All Fields Editable]
        O2 --> O3[Save Directly]
        O3 --> O4[Status: Approved]
        O4 --> O5[Changes Visible Immediately]
    end
    
    subgraph OwnerAutoOff[Owner - Auto-Approve OFF]
        P1[Edit Property] --> P2{Field Type?}
        P2 -->|Approval Required| P3[Create Edit Request]
        P2 -->|Auto-Approval| P4[Save Directly]
        P3 --> P5[Status: Pending]
        P4 --> P6[Status: Approved]
        P5 --> P7[Wait for Admin]
        P6 --> P8[Changes Visible Immediately]
        P7 --> P9[Admin Approves]
        P9 --> P10[Status: Approved]
        P10 --> P11[Changes Visible]
    end
    
    style AdminUser fill:#e1f5e1
    style OwnerAutoOn fill:#e1f5e1
    style OwnerAutoOff fill:#fff4e1
```

## 7. Mixed Field Changes Behavior

```mermaid
flowchart TD
    Start([User Changes Multiple Fields]) --> SplitFields[Split Fields by Type]
    
    SplitFields --> ApprovalFields[Approval Fields:<br/>Title, Description, Images]
    SplitFields --> AutoFields[Auto-Approval Fields:<br/>Price, Facilities, Parameters]
    
    ApprovalFields --> CreateRequest[Create Edit Request]
    AutoFields --> SaveImmediate[Save Immediately]
    
    CreateRequest --> PendingStatus[Property Status: Pending]
    SaveImmediate --> ApprovedFields[Fields Status: Approved]
    
    PendingStatus --> UserSees1[User Sees:<br/>Approval Fields Pending]
    ApprovedFields --> UserSees2[User Sees:<br/>Auto Fields Saved]
    
    UserSees1 --> Popup[Detailed Popup Shows:<br/>- Fields requiring approval<br/>- Fields saved immediately]
    UserSees2 --> Popup
    
    Popup --> AdminReview[Admin Reviews Approval Fields]
    AdminReview --> FinalApproved[All Fields Approved]
    
    FinalApproved --> AllVisible[All Changes Visible]
```

## 8. Backend Processing Flow

```mermaid
flowchart TD
    ReceiveRequest[Receive API Request] --> LoadProperty[Load Property from DB]
    LoadProperty --> CheckOwner{isOwnerEdit?<br/>added_by != 0}
    
    CheckOwner -->|No Admin| AdminPath[Admin Path:<br/>Skip All Checks]
    CheckOwner -->|Yes Owner| CheckAutoApprove{autoApproveEdited?}
    
    CheckAutoApprove -->|Yes| AutoApprovePath[Auto-Approve Path:<br/>Skip Approval Checks]
    CheckAutoApprove -->|No| CheckApprovalFields{hasApprovalRequiredChanges?}
    
    CheckApprovalFields -->|Yes| FilterFields[Filter Approval Fields]
    FilterFields --> CreateEditRequest[Create PropertyEditRequest]
    CreateEditRequest --> SaveNonApproval[Save Non-Approval Fields]
    SaveNonApproval --> SetPending[Set request_status = pending]
    
    CheckApprovalFields -->|No| SaveAll[Save All Fields]
    
    AdminPath --> SaveAll
    AutoApprovePath --> SaveAll
    
    SaveAll --> SetApproved[Set request_status = approved]
    SetPending --> SaveProperty[Save Property to DB]
    SetApproved --> SaveProperty
    
    SaveProperty --> SaveFacilities[Save Facilities if changed]
    SaveFacilities --> SaveParameters[Save Parameters if changed]
    SaveParameters --> ReturnResponse[Return Response to Frontend]
```

## Diagram Legend

### Colors
- **Green:** Immediate save/approved paths
- **Yellow:** Pending approval paths
- **Blue:** Decision points
- **Gray:** End states

### Shapes
- **Rectangle:** Process/action
- **Diamond:** Decision point
- **Rounded Rectangle:** Start/end state
- **Parallelogram:** Data/state

## Key Insights from Diagrams

1. **Three Main Paths:**
   - Admin: Always direct save
   - Owner + Auto-Approve ON: Always direct save
   - Owner + Auto-Approve OFF: Conditional (based on field types)

2. **Selective Approval:**
   - Only specific fields trigger approval
   - Other fields save immediately even when approval is required

3. **Mixed Behavior:**
   - When both types are changed, system handles them separately
   - User sees both immediate saves and pending approvals

4. **Clear Separation:**
   - Approval logic is separate from save logic
   - Non-approval fields always save immediately

